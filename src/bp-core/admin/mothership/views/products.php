<?php
/**
 * Products view template for displaying available add-ons.
 *
 * @package BuddyBoss\Core\Admin\Mothership
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// The dependency gates below rely on is_plugin_active(); this view renders on
// the admin Add-ons page where plugin.php is loaded, but guard for symmetry.
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Collected while rendering; feeds the in-place dependency updater printed
// after the grid (child product slug => required plugin dirnames, plugin
// dirname => rendered product slug, the active state of required plugins that
// have no card on this page, and parents with active dependents that have no
// card here — their Deactivate must stay gated regardless of carded children).
$bb_dependency_children  = array();
$bb_product_dirs         = array();
$bb_external_parents     = array();
$bb_external_dependents  = array();

// Disabled-button styling for the dependency gates below. Attached to the
// vendor stylesheet handle enqueued by AddonsManager::enqueueAssets() just
// before this view renders — late-enqueued admin styles print in the footer,
// and the inline data rides along with the handle.
wp_add_inline_style(
	'mosh-addons-css',
	'#mosh-admin-addons .mosh-product-action button[disabled] { opacity: 0.5; cursor: not-allowed; }'
);
?>

<div id="mosh-admin-addons" class="wrap">
	<h3>
		<form method="post" action="">
			<input type="submit"
				class="button button-secondary"
				name="submit-button-mosh-refresh-addon"
				value="<?php esc_attr_e( 'Refresh Add-ons', 'buddyboss-platform' ); ?>"
			>
			<input type="search"
				id="mosh-products-search"
				placeholder="<?php esc_attr_e( 'Search add-ons', 'buddyboss-platform' ); ?>"
			>
		</form>
	</h3>
	<?php if ( ! empty( $products ) ) : ?>
		<div id="mosh-products-container">
			<div class="mosh-products">
				<?php foreach ( $products as $product ) : ?>
				<div class="mosh-product mosh-product-status-<?php echo esc_attr( $product->status ); ?>">
					<div class="mosh-product-inner">
						<?php if ( $product->updateAvailable ) : ?>
						<div class="update-message notice inline notice-warning notice-alt mosh-product-update-message">
							<p>
								<?php esc_html_e( 'New version available.', 'buddyboss-platform' ); ?>
								<button class="button-link mosh-product-update-button" type="button"><?php esc_html_e( 'Update now', 'buddyboss-platform' ); ?></button>
							</p>
						</div>
						<?php endif; ?>
						<div class="mosh-product-details">
							<div class="mosh-product-image">
								<img src="<?php echo esc_url( $product->image ); ?>"
									alt="<?php echo esc_attr( $product->list_name ); ?>"
								>
							</div>
							<div class="mosh-product-info">
								<h2 class="mosh-product-name">
									<?php echo esc_html( $product->name ); ?>
								</h2>
								<p><?php echo esc_html( $product->description ); ?></p>
							</div>
						</div>
						<?php
						// Dependency gate: an installed-but-inactive add-on whose
						// "Requires Plugins" dependencies are not active cannot be
						// activated (core's activate_plugin() refuses), so disable
						// the button instead of failing on click.
						$bb_unmet_requires    = array();
						$bb_active_dependents = false;
						if ( 'plugin' === $product->extension_type && ! empty( $product->main_file ) ) {
							// Map plugin dirname => product slug so the updater can
							// resolve parent cards and the acted-on card.
							$bb_product_dirs[ dirname( $product->main_file ) ] = $product->slug;

							if ( 'not-installed' !== $product->status && class_exists( 'WP_Plugin_Dependencies' ) ) {
								WP_Plugin_Dependencies::initialize();
								$bb_dependency_names = WP_Plugin_Dependencies::get_dependency_names( $product->main_file );

								if ( ! empty( $bb_dependency_names ) ) {
									$bb_dependency_children[ $product->slug ] = array_keys( $bb_dependency_names );
								}

								if ( 'inactive' === $product->status ) {
									foreach ( $bb_dependency_names as $bb_dependency_slug => $bb_dependency_name ) {
										$bb_dependency_file = WP_Plugin_Dependencies::get_dependency_filepath( $bb_dependency_slug );
										if ( false === $bb_dependency_file || ! is_plugin_active( $bb_dependency_file ) ) {
											$bb_unmet_requires[] = $bb_dependency_name;
										}
									}
								} elseif ( 'active' === $product->status ) {
									// Deactivating a plugin other active plugins require
									// would orphan them (deactivate_plugins() has no
									// dependency guard) — disable Deactivate, matching
									// the plugins.php screen's own protection.
									$bb_active_dependents = WP_Plugin_Dependencies::has_active_dependents( $product->main_file );
								}
							}
						}
						?>
						<div class="mosh-product-actions mosh-clearfix">
							<div class="mosh-product-status">
								<strong>
									<?php
									printf(
										// Translators: %s: add-on status label.
										esc_html__( 'Status: %s', 'buddyboss-platform' ),
										sprintf( '<span class="mosh-product-status-label">%s</span>', esc_html( $product->statusLabel ) )
									);
									?>
								</strong>
							</div>
							<div class="mosh-product-action">
								<button type="button"
									data-slug="<?php echo esc_attr( $product->slug ); ?>"
									data-extension-type="<?php echo esc_attr( $product->extension_type ); ?>"
									<?php disabled( ! empty( $bb_unmet_requires ) || $bb_active_dependents ); ?>
								>
									<i class="<?php echo esc_attr( $product->iconClass ); ?>"></i>
									<?php echo esc_html( $product->buttonLabel ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php else : ?>
		<h3><?php esc_html_e( 'There were no Add-ons found for your License Key.', 'buddyboss-platform' ); ?></h3>
	<?php endif; ?>

	<?php
	if ( ! empty( $bb_dependency_children ) && class_exists( 'WP_Plugin_Dependencies' ) ) {
		WP_Plugin_Dependencies::initialize();

		// Active state of required plugins that have no card on this page —
		// their state cannot change from here, so the render-time value holds.
		foreach ( $bb_dependency_children as $bb_child_deps ) {
			foreach ( $bb_child_deps as $bb_dep_slug ) {
				if ( ! isset( $bb_product_dirs[ $bb_dep_slug ] ) && ! isset( $bb_external_parents[ $bb_dep_slug ] ) ) {
					$bb_dep_file = WP_Plugin_Dependencies::get_dependency_filepath( $bb_dep_slug );
					$bb_external_parents[ $bb_dep_slug ] = ( false !== $bb_dep_file && is_plugin_active( $bb_dep_file ) );
				}
			}
		}

		// Parents with an ACTIVE dependent that has no card here: the in-place
		// updater can only observe carded children, so such parents' Deactivate
		// must stay disabled no matter what happens to carded siblings.
		foreach ( $bb_product_dirs as $bb_parent_dir => $bb_parent_product_slug ) {
			foreach ( WP_Plugin_Dependencies::get_dependents( $bb_parent_dir ) as $bb_dependent_file ) {
				if ( is_plugin_active( $bb_dependent_file ) && ! isset( $bb_product_dirs[ dirname( $bb_dependent_file ) ] ) ) {
					$bb_external_dependents[ $bb_parent_dir ] = true;
					break;
				}
			}
		}
	}
	?>
	<?php if ( ! empty( $bb_dependency_children ) ) : ?>
		<?php ob_start(); ?>
			// The bundled addons.js updates only the acted-on card in place, so a
			// child add-on's dependency-disabled button doesn't follow its parent
			// being (de)activated. Watch for success messages and, when the acted
			// plugin is a dependency of another card, re-evaluate the dependent
			// cards' buttons in place — no page reload. Actions on plugins nothing
			// depends on are ignored.
			( function () {
				var container = document.getElementById( 'mosh-admin-addons' );
				if ( ! container || 'undefined' === typeof MutationObserver ) {
					return;
				}

				var deps = <?php echo wp_json_encode( array( 'children' => $bb_dependency_children, 'dirs' => $bb_product_dirs, 'external' => $bb_external_parents, 'externalDependents' => (object) $bb_external_dependents ), JSON_HEX_TAG | JSON_HEX_AMP ); ?>;

				function isParentActive( dirSlug ) {
					var productSlug = deps.dirs[ dirSlug ];
					if ( ! productSlug ) {
						return !! deps.external[ dirSlug ];
					}
					var btn = container.querySelector( 'button[data-slug="' + productSlug + '"]' );
					var card = btn && btn.closest( '.mosh-product' );
					return !! ( card && card.classList.contains( 'mosh-product-status-active' ) );
				}

				function refreshChildren( actedDir ) {
					Object.keys( deps.children ).forEach( function ( childSlug ) {
						var childDeps = deps.children[ childSlug ];
						if ( -1 === childDeps.indexOf( actedDir ) ) {
							return;
						}
						var btn = container.querySelector( 'button[data-slug="' + childSlug + '"]' );
						var card = btn && btn.closest( '.mosh-product' );
						// Only inactive cards carry a dependency-gated Activate.
						if ( ! btn || ! card || ! card.classList.contains( 'mosh-product-status-inactive' ) ) {
							return;
						}
						btn.disabled = ! childDeps.every( isParentActive );
					} );
				}

				function isChildActive( childSlug ) {
					var btn = container.querySelector( 'button[data-slug="' + childSlug + '"]' );
					var card = btn && btn.closest( '.mosh-product' );
					return !! ( card && card.classList.contains( 'mosh-product-status-active' ) );
				}

				// A child was (de)activated: its parents' Deactivate buttons must
				// re-gate — deactivating a plugin with active dependents would
				// orphan them, so the button is disabled while any dependent is on.
				function refreshParents( actedChildSlug ) {
					deps.children[ actedChildSlug ].forEach( function ( parentDir ) {
						var parentSlug = deps.dirs[ parentDir ];
						if ( ! parentSlug ) {
							return;
						}
						var btn = container.querySelector( 'button[data-slug="' + parentSlug + '"]' );
						var card = btn && btn.closest( '.mosh-product' );
						// Only active cards carry a Deactivate to gate.
						if ( ! btn || ! card || ! card.classList.contains( 'mosh-product-status-active' ) ) {
							return;
						}
						// Non-carded active dependents can't be observed in the DOM —
						// keep Deactivate gated whenever any exist for this parent.
						btn.disabled = !! deps.externalDependents[ parentDir ] || Object.keys( deps.children ).some( function ( childSlug ) {
							return -1 !== deps.children[ childSlug ].indexOf( parentDir ) && isChildActive( childSlug );
						} );
					} );
				}

				var observer = new MutationObserver( function ( mutations ) {
					for ( var i = 0; i < mutations.length; i++ ) {
						var added = mutations[ i ].addedNodes;
						for ( var j = 0; j < added.length; j++ ) {
							var node = added[ j ];
							if ( ! node.classList || ! node.classList.contains( 'mosh-product-message-success' ) ) {
								continue;
							}
							var card = node.closest ? node.closest( '.mosh-product' ) : null;
							var btn = card && card.querySelector( 'button[data-slug]' );
							if ( ! btn ) {
								continue;
							}
							// Parent action → re-gate dependent children's Activate.
							Object.keys( deps.dirs ).forEach( function ( dirSlug ) {
								if ( deps.dirs[ dirSlug ] === btn.dataset.slug ) {
									refreshChildren( dirSlug );
								}
							} );
							// Child action → re-gate its parents' Deactivate.
							if ( deps.children[ btn.dataset.slug ] ) {
								refreshParents( btn.dataset.slug );
							}
						}
					}
				} );
				observer.observe( container, { childList: true, subtree: true } );
			} )();
		<?php
		// Ride along with the vendor addons.js handle enqueued by
		// AddonsManager::enqueueAssets() (footer script), instead of printing
		// a raw inline <script> block mid-page.
		wp_add_inline_script( 'mosh-addons-js', ob_get_clean() );
		?>
	<?php endif; ?>
</div>
