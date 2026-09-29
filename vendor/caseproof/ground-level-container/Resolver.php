<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container;

/**
 * Resolves class dependencies via constructor reflection and docblock annotations.
 *
 * Auto-wires services by reflecting on constructor parameters and matching them
 * to container entries via type hints and @inject annotations.
 */
class Resolver
{
    /**
     * Matches an @inject tag line in a property docblock (not prose mentioning @inject).
     *
     * Tag forms: " * @inject value" or "/** @inject value".
     */
    private const INJECT_TAG_PATTERN = '/(?:\\/\\*\\*\\s*|\\*\\s*)@inject\\s+(\\S+)/';
    /**
     * The container instance.
     *
     * @var \GroundLevel\Container\Container
     */
    private Container $container;
    /**
     * Classes currently being resolved, used to detect circular dependencies.
     *
     * @var array<string, true>
     */
    private array $resolving = [];
    /**
     * Cached result of whether opcache is stripping docblocks in the current SAPI.
     *
     * @var boolean|null
     */
    private static ?bool $docblocksStrippedCache = null;
    /**
     * Creates a new Resolver instance.
     *
     * @param \GroundLevel\Container\Container $container The container instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * Instantiate a class by auto-wiring its constructor dependencies.
     *
     * @throws \GroundLevel\Container\Exception If the class cannot be resolved.
     *
     * @param  string      $class         The class to instantiate.
     * @param  string|null $concreteClass The concrete class if $class is an interface/abstract.
     * @return object
     */
    public function resolve(string $class, ?string $concreteClass = null) : object
    {
        $targetClass = $concreteClass ?? $class;
        if (isset($this->resolving[$targetClass])) {
            $chain = \implode(' -> ', \array_keys($this->resolving)) . ' -> ' . $targetClass;
            throw new Exception("Circular dependency detected: {$chain}");
        }
        if (!\class_exists($targetClass)) {
            throw new Exception("Cannot resolve [{$targetClass}]: class does not exist.");
        }
        $this->resolving[$targetClass] = \true;
        try {
            $reflection = new \ReflectionClass($targetClass);
            $constructor = $reflection->getConstructor();
            if (null === $constructor) {
                return new $targetClass();
            }
            $injectMap = $this->buildInjectMap($reflection);
            $args = [];
            foreach ($constructor->getParameters() as $param) {
                $args[] = $this->resolveParameter($param, $injectMap);
            }
            return $reflection->newInstanceArgs($args);
        } finally {
            unset($this->resolving[$targetClass]);
        }
    }
    /**
     * Build a map of property name => container ID from @inject annotations.
     *
     * Supports both literal IDs and class constant references:
     * - string literal: `@inject service.prefix`
     * - constant:       `@inject \GroundLevel\Component\ComponentServiceProvider::PARAM_PREFIX`
     *
     * @param  \ReflectionClass $reflection The class reflection.
     * @return array<string, string> Map of property name => container ID.
     */
    private function buildInjectMap(\ReflectionClass $reflection) : array
    {
        $map = [];
        foreach ($reflection->getProperties() as $prop) {
            $doc = $prop->getDocComment();
            if (\false !== $doc) {
                $value = self::parseInjectTag($doc);
                if (null !== $value) {
                    $map[$prop->getName()] = $this->resolveInjectId($value);
                }
                continue;
            }
            // OPcache with save_comments=0 strips docblocks from the compiled class,
            // so getDocComment() returns false and @inject DI silently breaks (fatal
            // on required scalar params such as Class::$prefix on hosts like Kinsta).
            // Recover the annotation from the on-disk source, which opcache never
            // rewrites. Only worth attempting when comments are actually stripped;
            // otherwise a false docblock genuinely means the property has none.
            if (!self::docblocksStripped()) {
                continue;
            }
            $value = self::injectValueFromSource($prop);
            if (null !== $value) {
                $map[$prop->getName()] = $this->resolveInjectId($value);
            }
        }
        return $map;
    }
    /**
     * Whether opcache is stripping docblocks from compiled classes in the current
     * SAPI (opcache enabled with opcache.save_comments=0). When false, a missing
     * docblock is genuine and there is nothing to recover from source.
     *
     * @return boolean
     */
    private static function docblocksStripped() : bool
    {
        if (null === self::$docblocksStrippedCache) {
            $enabled = 'cli' === \PHP_SAPI ? (bool) \ini_get('opcache.enable_cli') : (bool) \ini_get('opcache.enable');
            self::$docblocksStrippedCache = $enabled && !(bool) \ini_get('opcache.save_comments');
        }
        return self::$docblocksStrippedCache;
    }
    /**
     * Recover a property's @inject value from the on-disk source when opcache has
     * stripped the docblock from the compiled class. The source file is never
     * rewritten by opcache, so re-scanning it yields the original annotation.
     *
     * @param  \ReflectionProperty $prop The property whose annotation to recover.
     * @return string|null The raw @inject value, or null if none.
     */
    private static function injectValueFromSource(\ReflectionProperty $prop) : ?string
    {
        static $cache = [];
        $file = $prop->getDeclaringClass()->getFileName();
        if (\false === $file || !\is_file($file)) {
            return null;
        }
        if (!isset($cache[$file])) {
            $cache[$file] = self::parseInjectAnnotations($file);
        }
        return $cache[$file][$prop->getName()] ?? null;
    }
    /**
     * Parse a source file into a map of property name => raw @inject value.
     *
     * @inject only ever annotates a class property, so each @inject docblock is
     * paired with the next T_VARIABLE, skipping attributes, modifiers and the type.
     * A structural keyword or block boundary before that variable means the
     * docblock is not a property docblock (e.g. prose mentioning @inject) and is
     * ignored. Spurious pairings never surface, since buildInjectMap() only queries
     * names that are real reflected properties.
     *
     * Returns an empty map when the file cannot be read (e.g. unreadable phar paths).
     *
     * @param  string $file The absolute path to the source file.
     * @return array<string, string> Map of property name => raw @inject value.
     */
    private static function parseInjectAnnotations(string $file) : array
    {
        if (!\is_readable($file)) {
            return [];
        }
        $contents = @\file_get_contents($file);
        if (\false === $contents) {
            return [];
        }
        // Cheap gate: a file with no annotation at all needs no tokenizing.
        if (\false === \strpos($contents, '@inject')) {
            return [];
        }
        $stopTokens = [\T_FUNCTION, \T_CONST, \T_CLASS, \T_INTERFACE, \T_TRAIT];
        $tokens = \token_get_all($contents);
        $count = \count($tokens);
        $map = [];
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!\is_array($token) || \T_DOC_COMMENT !== $token[0]) {
                continue;
            }
            $value = self::parseInjectTag($token[1]);
            if (null === $value) {
                continue;
            }
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];
                if (\is_array($next)) {
                    if (\T_VARIABLE === $next[0]) {
                        $map[\ltrim($next[1], '$')] = $value;
                        break;
                    }
                    if (\in_array($next[0], $stopTokens, \true)) {
                        break;
                    }
                    continue;
                }
                if (';' === $next || '{' === $next || '}' === $next) {
                    break;
                }
            }
        }
        return $map;
    }
    /**
     * Extract the raw @inject value from a property doc comment.
     *
     * Only matches @inject on tag lines (after `/**` or ` *`), ignoring prose that
     * merely mentions @inject in a description sentence.
     *
     * @param  string $docblock A property doc comment or T_DOC_COMMENT token text.
     * @return string|null The raw @inject value, or null if no tag is present.
     */
    private static function parseInjectTag(string $docblock) : ?string
    {
        if (\preg_match(self::INJECT_TAG_PATTERN, $docblock, $matches)) {
            return $matches[1];
        }
        return null;
    }
    /**
     * Resolve a @inject value to a container ID.
     *
     * If the value contains `::` it is treated as a class constant reference
     * and resolved via PHP's constant() function. Otherwise it is used as-is.
     *
     * @throws \GroundLevel\Container\Exception If the constant reference is undefined.
     *
     * @param  string $reference The raw annotation value.
     * @return string The resolved container ID.
     */
    private function resolveInjectId(string $reference) : string
    {
        if (\strpos($reference, '::') !== \false) {
            $fqcn = \ltrim($reference, '\\');
            if (!\defined($fqcn)) {
                throw new Exception("@inject references undefined constant: {$fqcn}");
            }
            return (string) \constant($fqcn);
        }
        return $reference;
    }
    /**
     * Resolve a single constructor parameter.
     *
     * @throws \GroundLevel\Container\Exception If the parameter cannot be resolved.
     *
     * @param  \ReflectionParameter  $param     The parameter to resolve.
     * @param  array<string, string> $injectMap Map of property name => container ID.
     * @return mixed
     */
    private function resolveParameter(\ReflectionParameter $param, array $injectMap)
    {
        $type = $param->getType();
        $name = $param->getName();
        // 1. Explicit @inject binding (scalar params, or a specific service by id).
        if (isset($injectMap[$name])) {
            $id = $injectMap[$name];
            if (!$this->container->has($id)) {
                throw new Exception("@inject on \${$name} references '{$id}' which is not registered in the container.");
            }
            return $this->container->get($id);
        }
        // 2. Auto-wire by type hint (services).
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $this->container->get($type->getName());
        }
        // 3. Fall back to default value.
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }
        // 4. Allow nullable.
        if (null !== $type && $type->allowsNull()) {
            return null;
        }
        throw new Exception("Cannot resolve parameter \${$name} in {$param->getDeclaringClass()->getName()}.");
    }
}
