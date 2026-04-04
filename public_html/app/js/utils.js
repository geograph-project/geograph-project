//useful functions for the app, but also standalone iframes, for communicating with the app

export function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

export function escapeRegex(string) {
    // This replaces special regex characters with their escaped version
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

//actully seems like best way to request navigation is via an event!
export function navigateTo(path, options) {
    const isInsideIframe = window.self !== window.top;
    const target = isInsideIframe ? window.parent : window;

    const event = new CustomEvent('request-navigation', {
        detail: { path, options },
        bubbles: true
    });
    target.dispatchEvent(event);
}

export function updateAppState(detail) {
    const isInsideIframe = window.self !== window.top;
    const target = isInsideIframe ? window.parent : window;
    const event = new CustomEvent('update-app-state', {
        detail,
        bubbles: true
    });
    target.dispatchEvent(event);
}

export function setupSettingsListener() {
    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;
       	try {
            const data = JSON.parse(event.data);
            if (data.settings) {
                document.body.classList.toggle('dark-mode', data.settings.darkMode);
            }
            if (data.settings && data.settings.uploadMaxDimension) {
                window.uploadMaxDimension = parseInt(data.settings.uploadMaxDimension, 10);
            }
        } catch (e) {
    		//just catching if fail to decode JSON
    	}
    });
}

export function openModal(id) {
    const modal = document.getElementById(id);
    modal.showModal();
    modal.scrollTop = 0;
}
export function closeModal(id) {
    document.getElementById(id).close();
}



/**
 * GeographRenderer
 * A utility to convert Geograph 'article' text into semantic HTML.
 */

export const GeographRenderer = {
  /**
   * Main render function
   * @param {string} text - The raw source text from the API
   * @returns {string} - Semantic HTML string
   */
  render(text) {
    if (!text) return "";

    const lines = text.split(/\r?\n/);
    let htmlOutput = [];
    let currentListType = null; // Stores 'ul' or 'ol'

    // Helper to close open lists
    const closeList = () => {
      if (currentListType) {
        htmlOutput.push(`</${currentListType}>`);
        currentListType = null;
      }
    };

    lines.forEach((line) => {
      const trimmedLine = line.trim();

      // 1. Handle Unordered Lists (*)
      if (trimmedLine.startsWith('*')) {
        if (currentListType !== 'ul') {
          closeList();
          htmlOutput.push('<ul>');
          currentListType = 'ul';
        }
        const content = trimmedLine.replace(/^\*\s*/, '');
        htmlOutput.push(`  <li>${this.parseInline(content)}</li>`);
      } 
      
      // 2. Handle Ordered Lists (-)
      else if (trimmedLine.startsWith('-')) {
        if (currentListType !== 'ol') {
          closeList();
          htmlOutput.push('<ol>');
          currentListType = 'ol';
        }
        const content = trimmedLine.replace(/^-\s*/, '');
        htmlOutput.push(`  <li>${this.parseInline(content)}</li>`);
      } 
      
      // 3. Handle Empty Lines (Paragraph Breaks)
      else if (trimmedLine === "") {
        closeList();
        htmlOutput.push('<br>');
      } 
      
      // 4. Handle Headers and Plain Text
      else {
        closeList();
        // If it's a header [h3], render it directly
        if (trimmedLine.startsWith('[h3]')) {
          htmlOutput.push(this.parseInline(trimmedLine));
        } else {
          // Otherwise, treat as a standard line of text
          htmlOutput.push(`<p>${this.parseInline(trimmedLine)}</p>`);
        }
      }
    });

    closeList();
    return htmlOutput.join('\n');
  },

  /**
   * Processes inline BBCode tags
   * @param {string} text
   * @returns {string}
   */
  parseInline(text) {
    let result = text;

    const rules = [
      { reg: /\[h3\](.*?)\[\/h3\]/gi, rep: '<h3>$1</h3>' },
      { reg: /\[b\](.*?)\[\/b\]/gi, rep: '<strong>$1</strong>' },
      { reg: /\[i\](.*?)\[\/i\]/gi, rep: '<em>$1</em>' },
      { reg: /\[url=(.*?)\](.*?)\[\/url\]/gi, rep: '<a href="$1" target="_blank" rel="noopener">$2</a>' }
    ];

    rules.forEach(rule => {
      result = result.replace(rule.reg, rule.rep);
    });

    return result;
  }
};
