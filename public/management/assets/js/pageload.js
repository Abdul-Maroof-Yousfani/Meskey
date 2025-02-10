hidePreloader();

let isContentLoaded = false;


function blockSpecificScript(scriptSrc) {
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src.includes(scriptSrc)) {
            scripts[i].disabled = true;
        }
    }
}

function enableSpecificScript(scriptSrc) {
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src.includes(scriptSrc)) {
            scripts[i].disabled = false;
        }
    }
}

function showPreloader() {
    $('#preloader').fadeIn();
}

function hidePreloader() {
    $('#preloader').fadeOut();
}

function loadPageContent(url, updateHistory = true,is_new_tab=false) {
    blockSpecificScript('scripts.js');
    showPreloader();

    fetch(url)
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error("404: Page Not Found");
                } else if (response.status === 500) {
                    throw new Error("500: Internal Server Error");
                } else {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }
            }
            return response.text();
        })
        .then(html => {
            document.open();
            document.write(html);
            document.close();

            enableSpecificScript('scripts.js');
            hidePreloader();
            blockSpecificScript('pageload.js');
      

            if (updateHistory) {
                window.history.pushState({ url }, '', url);
            }

            // Call your custom function again
            customPostLoadActions();
        })
        .catch(error => {
            console.error('Error loading content:', error);
            hidePreloader();

            // Show custom error UI inside the body
            document.body.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <h1 style="color: red;">Error Loading Page</h1>
                    <p>${getErrorMessage(error.message)}</p>
                    <button id="refreshPage"
                            style="background-color: #3085d6; color: white; border: none; padding: 10px 20px; cursor: pointer; font-size: 16px;">
                        Refresh Page
                    </button>
                </div>
            `;

            document.getElementById('refreshPage').addEventListener('click', () => {
                loadPageContent(window.location.href);
            });
        });
}

function getErrorMessage(errorMsg) {
    if (errorMsg.includes("404")) {
        return "The requested page was not found.";
    } else if (errorMsg.includes("500")) {
        return "Internal Server Error. Please try again later.";
    } else if (errorMsg.includes("Failed to fetch")) {
        return "Network Error: Please check your internet connection.";
    }
    return "Unexpected Error: Please try again later.";
}

function customPostLoadActions() {
    console.log('Custom post-load actions executed.');
    // Add your logic here, e.g., event bindings or UI updates
}

window.addEventListener('popstate', (event) => {
    if (event.state && event.state.url) {
        loadPageContent(event.state.url, false);
    }
});

$(document).ready(function () {
    loadPageContent(window.location.href);
});
