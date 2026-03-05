/**
 * Home View Module
 */

export function render() {
    return `
        <div class="view home-view">
            <h2>Welcome to PMA</h2>
            <p>Your Personal Management Application.</p>
            <div class="content-body">
                <button data-route="/app/iframe-demo" class="demo-btn">Try Persistent Iframe Demo</button>
            </div>
        </div>
    `;
}

export function onMount() {
    console.log('Home View Mounted');
}
