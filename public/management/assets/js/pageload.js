hidePreloader();

let isContentLoaded = false;

function blockSpecificScript(scriptSrc) {
  const scripts = document.getElementsByTagName("script");
  for (let i = 0; i < scripts.length; i++) {
    if (scripts[i].src.includes(scriptSrc)) {
      scripts[i].disabled = true;
    }
  }
}

function enableSpecificScript(scriptSrc) {
  const scripts = document.getElementsByTagName("script");
  for (let i = 0; i < scripts.length; i++) {
    if (scripts[i].src.includes(scriptSrc)) {
      scripts[i].disabled = false;
    }
  }
}

function showPreloader() {
  $("#preloader").fadeIn();
}

function hidePreloader() {
  $("#preloader").fadeOut();
}

function loadPageContent(url, updateHistory = true, is_new_tab = false) {
  if (!url) return;
  if (is_new_tab) {
    window.open(url, "_blank");
  } else {
    window.location.href = url;
  }
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
  console.log("Custom post-load actions executed.");
}

window.addEventListener("popstate", (event) => {
  if (event.state && event.state.url) {
    loadPageContent(event.state.url, false);
  }
});

$(document).ready(function () {
  loadPageContent(window.location.href);
});
