import { escapeHTML, GeographRenderer } from '/app/js/utils.js';

export function render() {
  return `
    <div class="view">
      <div id="article-content">Loading...</div>
    </div>
    <style>
	#article-content ul, #article-content ol {
		margin-left:22px;
	}
	#article-content li, #article-content p {
		margin:4px 0;
	}
	#article-content h3, #article-content hr { margin: 16px 0; }

    </style>`; //need to restore default indent
}

export async function onMount() {
  const container = document.getElementById('article-content');

  try {
    const response = await fetch('/article/source.php?url=Geograph-Introductory-letter&utf8=1'); //our default charset is not utf8!
    const plainText = await response.text();

    container.innerHTML = GeographRenderer.render(plainText);

  } catch (e) {
console.log(e);
    container.innerHTML = '<p>Failed to load terms.</p>';
  }
}
