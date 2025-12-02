/**
 * Dynamic HTML Loader - AJAX based HTML content loader
 * Usage: fetchDynamicHTML(route, elementId, params, options)
 * Author: Your Name
 * Version: 1.0
 */

// Main function
function fetchDynamicHTML(
    route,
    responseAppendDivId,
    params = {},
    options = {}
  ) {
    const targetDiv = document.getElementById(responseAppendDivId);
  
    if (!targetDiv) {
      console.error(`Target div with ID '${responseAppendDivId}' not found`);
      return null;
    }
  
    // Store original content for restoration
    if (!targetDiv.dataset.originalContent) {
      targetDiv.dataset.originalContent = targetDiv.innerHTML;
    }
  
    // Show loading state
    showLoadingState(targetDiv, options);
  
    // Before fetch callback
    if (typeof options.beforeFetch === "function") {
      options.beforeFetch(targetDiv, route, params);
    }
  
    const xhr = new XMLHttpRequest();
    let isAborted = false;
  
    // Progress tracking
    if (options.showProgress) {
      xhr.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
          const percentComplete = (e.loaded / e.total) * 100;
          if (typeof options.onProgress === "function") {
            options.onProgress(percentComplete, e.loaded, e.total);
          }
        }
      });
    }
  
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && !isAborted) {
        handleResponse(xhr, targetDiv, route, responseAppendDivId, params, options);
      }
    };
  
    xhr.onerror = function() {
      if (!isAborted) {
        handleError(new Error('Network error'), targetDiv, route, responseAppendDivId, params, options);
      }
    };
  
    // Set timeout
    if (options.timeout) {
      xhr.timeout = options.timeout;
      xhr.ontimeout = function() {
        handleError(new Error(`Request timeout after ${options.timeout}ms`), targetDiv, route, responseAppendDivId, params, options);
      };
    }
  
    // Open and send
    xhr.open(options.method || "POST", route, true);
    
    // Set headers
    const headers = {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
      ...options.headers
    };
    
    Object.keys(headers).forEach(key => {
      xhr.setRequestHeader(key, headers[key]);
    });
  
    xhr.send(JSON.stringify(params));
  
    // Return controller object
    return {
      abort: function() {
        isAborted = true;
        xhr.abort();
        targetDiv.innerHTML = '<div class="alert alert-info">Request cancelled</div>';
      },
      xhr: xhr
    };
  }
  
  // Helper functions
  function showLoadingState(targetDiv, options) {
    if (options.showLoading !== false) {
      targetDiv.innerHTML = options.loadingHTML || 
        `<div class="text-center p-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="mt-2">${options.loadingText || 'Loading...'}</div>
         </div>`;
    }
  }
  
  function handleResponse(xhr, targetDiv, route, divId, params, options) {
    if (xhr.status === 200) {
      const html = xhr.responseText;
      
      // Success callback
      if (typeof options.onSuccess === "function") {
        options.onSuccess(html, targetDiv, route, params, xhr);
      } else {
        targetDiv.innerHTML = html;
      }
  
      // Dispatch success event
      document.dispatchEvent(
        new CustomEvent("dynamicContentLoaded", {
          detail: { route, divId, params, html, xhr }
        })
      );
    } else {
      handleError(
        new Error(`HTTP ${xhr.status}: ${xhr.statusText}`), 
        targetDiv, route, divId, params, options, xhr
      );
    }
  }
  
  function handleError(error, targetDiv, route, divId, params, options, xhr = null) {
    console.error("Error fetching dynamic HTML:", error);
  
    // Error callback
    if (typeof options.onError === "function") {
      options.onError(error, targetDiv, route, params, xhr);
    } else {
      targetDiv.innerHTML = `
        <div class="alert alert-danger">
          <strong>Error!</strong> Failed to load content: ${error.message}
          ${xhr && xhr.responseText ? `<br><small>${xhr.responseText}</small>` : ''}
        </div>
        <button type="button" onclick="fetchDynamicHTML('${route}', '${divId}', ${JSON.stringify(params).replace(/'/g, "\\'")}, ${JSON.stringify(options).replace(/'/g, "\\'")})" 
                class="btn btn-sm btn-outline-primary mt-2">
          Retry
        </button>
        <button onclick="this.parentElement.innerHTML = \`${targetDiv.dataset.originalContent.replace(/`/g, '\\`')}\`" 
                class="btn btn-sm btn-outline-secondary mt-2 ms-1">
          Restore Original
        </button>
      `;
    }
  
    // Dispatch error event
    document.dispatchEvent(
      new CustomEvent("dynamicContentError", {
        detail: { route, divId, params, error, xhr }
      })
    );
  }
  
  // =============================================
  // USAGE EXAMPLES - Comment out what you don't need
  // =============================================
  
  /**
   * EXAMPLE 1: Basic Usage - Button Click
   * Usage: <button onclick="loadBasicForm()">Load Form</button>

  function loadBasicForm() {
    fetchDynamicHTML('/api/user-form', 'contentArea', {
      type: 'basic',
      user_id: 123
    });
  }
  
  /**
   * EXAMPLE 2: Select/OnChange Usage  
   * Usage: <select onchange="loadCities(this.value)">
 
  function loadCities(stateId) {
    fetchDynamicHTML('/api/get-cities', 'citiesContainer', {
      state_id: stateId
    }, {
      loadingText: 'Loading cities...'
    });
  }
  
  /**
   * EXAMPLE 3: With Custom Loading HTML
   */
//   function loadWithCustomLoader() {
//     fetchDynamicHTML('/api/products', 'productsDiv', {
//       category: 'electronics'
//     }, {
//       loadingHTML: `
//         <div class="text-center p-4">
//           <div class="spinner-grow text-warning"></div>
//           <div class="mt-2">Loading products, please wait...</div>
//         </div>
//       `
//     });
//   }
  
  /**
   * EXAMPLE 4: With Success Callback
  
  function loadWithCallback() {
    fetchDynamicHTML('/api/user-profile', 'profileSection', {
      user_id: 456
    }, {
      onSuccess: function(html, targetDiv, route, params) {
        targetDiv.innerHTML = html;
        // Initialize any JavaScript after content loads
        initializeUserProfile();
        console.log('Profile loaded successfully!');
      },
      onError: function(error, targetDiv, route, params) {
        targetDiv.innerHTML = `<div class="alert alert-warning">Profile load failed: ${error.message}</div>`;
      }
    });
  }
  
  /**
   * EXAMPLE 5: With Progress Tracking
   
  function loadWithProgress() {
    fetchDynamicHTML('/api/large-data', 'dataContainer', {
      report_type: 'annual'
    }, {
      showProgress: true,
      onProgress: function(percent, loaded, total) {
        console.log(`Loaded: ${percent.toFixed(2)}%`);
        // You can update a progress bar here
        updateProgressBar(percent);
      },
      timeout: 30000 // 30 seconds
    });
  }
  
  /**
   * EXAMPLE 6: GET Request
]
  function loadWithGet() {
    fetchDynamicHTML('/api/news?category=politics', 'newsContainer', {}, {
      method: 'GET'
    });
  }
  
  /**
   * EXAMPLE 7: With Custom Headers
   
  function loadWithAuth() {
    fetchDynamicHTML('/api/protected-data', 'secureContent', {
      action: 'view'
    }, {
      headers: {
        'Authorization': 'Bearer your-token-here',
        'API-Key': 'your-api-key'
      }
    });
  }
  
  /**
   * EXAMPLE 8: Abortable Request
   
  let currentRequest = null;
  
  function startLoad() {
    // Abort previous request if exists
    if (currentRequest) {
      currentRequest.abort();
    }
    
    currentRequest = fetchDynamicHTML('/api/search', 'resultsDiv', {
      query: document.getElementById('searchInput').value
    }, {
      loadingText: 'Searching...'
    });
  }
  
  function cancelLoad() {
    if (currentRequest) {
      currentRequest.abort();
      currentRequest = null;
    }
  }
  
  /**
   * EXAMPLE 9: Before Fetch Hook
   
  function loadWithBeforeHook() {
    fetchDynamicHTML('/api/data', 'contentDiv', {
      page: 1,
      limit: 10
    }, {
      beforeFetch: function(targetDiv, route, params) {
        // Disable buttons or show pre-loader
        targetDiv.style.opacity = '0.6';
        console.log('Starting fetch for:', route);
      },
      onSuccess: function(html, targetDiv) {
        targetDiv.style.opacity = '1';
      }
    });
  }
  
  /**
   * EXAMPLE 10: Event Listeners for Global Handling
   
  document.addEventListener('dynamicContentLoaded', function(e) {
    console.log('Content loaded:', e.detail);
    // You can add global success handling here
  });
  
  document.addEventListener('dynamicContentError', function(e) {
    console.error('Content load failed:', e.detail);
    // You can add global error handling here
  });
  
  /**
   * EXAMPLE 11: Auto-retry on Error
   
  function loadWithRetry(route, divId, params, options, retries = 3) {
    fetchDynamicHTML(route, divId, params, {
      ...options,
      onError: function(error, targetDiv, route, params) {
        if (retries > 0) {
          console.log(`Retrying... ${retries} attempts left`);
          setTimeout(() => {
            loadWithRetry(route, divId, params, options, retries - 1);
          }, 1000);
        } else {
          // Final error handling
          targetDiv.innerHTML = `
            <div class="alert alert-danger">
              Failed after multiple attempts: ${error.message}
            </div>
          `;
        }
      }
    });
  }
  
  // =============================================
  // HELPER FUNCTIONS
  // =============================================
  
  /**
   * Update progress bar (for progress tracking example)
   */
  function updateProgressBar(percent) {
    let progressBar = document.getElementById('loadingProgressBar');
    if (!progressBar) {
      progressBar = document.createElement('div');
      progressBar.id = 'loadingProgressBar';
      progressBar.className = 'progress mb-3';
      progressBar.innerHTML = '<div class="progress-bar" role="progressbar" style="width: 0%"></div>';
      document.body.appendChild(progressBar);
    }
    
    const bar = progressBar.querySelector('.progress-bar');
    bar.style.width = percent + '%';
    bar.textContent = Math.round(percent) + '%';
    
    if (percent >= 100) {
      setTimeout(() => progressBar.remove(), 1000);
    }
  }
  
  /**
   * Initialize user profile (for callback example)
   */
  function initializeUserProfile() {
    // Add any profile-specific JavaScript initialization here
    console.log('User profile initialized');
  }
  
  // =============================================
  // QUICK USAGE SNIPPETS - Copy and paste these
  // =============================================
  
  /*
  // SNIPPET 1: Basic load on click
  <button onclick="fetchDynamicHTML('/api/form', 'myDiv', {id: 123})">
    Load Content
  </button>
  
  // SNIPPET 2: Load on select change  
  <select onchange="fetchDynamicHTML('/api/data', 'output', {type: this.value})">
    <option value="a">Option A</option>
    <option value="b">Option B</option>
  </select>
  
  // SNIPPET 3: With loading text
  fetchDynamicHTML('/api/content', 'container', {page: 1}, {
    loadingText: 'Please wait...'
  });
  
  // SNIPPET 4: With custom error handling
  fetchDynamicHTML('/api/data', 'divId', {param: 'value'}, {
    onError: function(error, targetDiv) {
      targetDiv.innerHTML = '<div>Custom error: ' + error.message + '</div>';
    }
  });
  
  // SNIPPET 5: Disable loading indicator
  fetchDynamicHTML('/api/quick', 'divId', {quick: true}, {
    showLoading: false
  });
  */
  
  console.log('Dynamic HTML Loader loaded successfully!');