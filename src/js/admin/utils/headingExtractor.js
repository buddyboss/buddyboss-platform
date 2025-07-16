// Extract all h2 headings from a container, generate unique anchor IDs, and return an array of {el, text, anchor}
export function extractH2Headings(container) {
  if (!container) return [];
  const headings = Array.from(container.querySelectorAll('h2'));
  const anchors = {};
  return headings.map((el) => {
    let text = (el.textContent || el.innerText || '').trim();
    let anchor = text
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
    // Ensure unique anchor
    if (anchors[anchor]) {
      anchors[anchor]++;
      anchor = `${anchor}-${anchors[anchor]}`;
    } else {
      anchors[anchor] = 1;
    }
    return { el, text, anchor };
  });
} 