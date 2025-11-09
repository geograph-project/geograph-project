/**
 * Executes the callback immediately if the DOM is ready,
 * or attaches it to the 'DOMContentLoaded' event otherwise
 * (to handle late code injection, like jQuery's ready handler).
 */
function onDomReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
}

onDomReady(function() {

    // --- 1. Append CSS Styles to <head> ---
    const style = document.createElement('style');
    style.textContent = `
        #appeal_block {
            box-sizing: border-box;
            color: black;
            background-color: BlanchedAlmond;
            font-family: arial;
            line-height: 1.3em;
            padding: 10px;
            margin-bottom: 20px;
        }
        #appeal_block div.break {
            height: 10px;
        }
        #appeal_block a {
            color: blue;
        }
        #appeal_block a.closer {
            display: block;
            float: right;
            margin-top: -9px;
            margin-right: -9px;
            background-color: #e5d5bb;
            font-weight: bold;
            padding: 10px;
            color: brown;
            cursor: pointer;
        }
        #appeal_block div.float {
            float: right;
            margin-left: 10px;
            text-align: center;
        }
        #appeal_block div a.btn {
            padding: 10px;
            font-weight: bold;
            display: block;
            width: 150px;
            background-color: purple;
            color: white;
            border-radius: 10px;
            text-decoration: none;
        }
    `;
    document.head.appendChild(style);

    // --- 2. Create and Prepend HTML Block ---
    const mainContentBlock = document.getElementById('maincontent_block');

    if (mainContentBlock) {
        const survey_url = registered_user_servey
           ? "https://forms.gle/qQNhdzPGYL9rnFHA6" // Value if TRUE
           : "https://forms.gle/7QysivC6JBJCi2PU8"; // Value if FALSE

        const appealHTML = `
            <div id="appeal_block" data-nosnippet>
                <a class="closer" href="#">Close</a>
		Please spare a few minutes to answer our <a href="${survey_url}" target="survey" class="btn">2025 User Survey</a>!
            </div>
        `;
        mainContentBlock.insertAdjacentHTML('afterbegin', appealHTML);

        // --- 3. Add Click Listener for Closer ---
        const closerLink = document.querySelector('#appeal_block a.closer');
        const appealBlock = document.getElementById('appeal_block');

        if (closerLink && appealBlock) {
            closerLink.addEventListener('click', function(event) {
                event.preventDefault();

                // Hide the element
                appealBlock.style.display = 'none';

                // Use your existing createCookie function to set a 7-day cookie
                createCookie("survey", 1, 7);
            });
        }
    }
});
