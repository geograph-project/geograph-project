export function render() {
  // We provide a container where the content will eventually live
  return `
    <div class="view">
      <div id="terms-container">Loading terms...</div>
    </div>
    <style>
	#terms-container {
		max-width:60em;
	}
	#terms-container ul, #terms-container ol {
		margin-left:22px;
	}
	#terms-container li, #terms-container p {
		margin:4px 0;
	}
	#terms-container h3, #terms-container hr { margin: 16px 0; }

    </style>`; //need to restore default indent
}

export async function onMount() {
  const container = document.getElementById('terms-container');

  try {
    const response = await fetch('/help/terms');
    const htmlText = await response.text();

    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlText, 'text/html');

    const content = doc.querySelector('#maincontent');

    if (content) {
      container.innerHTML = content.innerHTML;
    } else {
      container.innerHTML = '<p>Error: Could not find content.</p>';
    }
  } catch (e) {
    container.innerHTML = '<p>Failed to load terms.</p>';
  }
}
