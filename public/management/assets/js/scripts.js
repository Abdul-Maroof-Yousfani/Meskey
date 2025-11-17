// function filterationCommon(url, loadmore = false, appenddiv = "filteredData") {
//   renderLoadingTable("#filteredData table", 10);
// }

// renderLoadingTable("#filteredData table", 12);

function getUniversalNumber(options, callback) {

  $.get('/generate-unique-no', options, function(response) {
      callback(response.unique_no);
  });
}



function filterationCommon(url, loadmore = false, appenddiv = "filteredData") {
  renderLoadingTable("#filteredData table", 10);

  var url = url;
  var loadmore = loadmore;
  var appenddiv = appenddiv;

  // Initialize Daterangepicker
  initializeDaterangepicker();

  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
  });

  // Debounce function to optimize input change handling
  function debounce(func, delay) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  $('input[name="daterange"]').daterangepicker(
    {
      opens: "left",
      // autoUpdateInput: false,
      locale: {
        cancelLabel: "Clear",
      },
    },
    function (start, end, label) {
      console.log(
        "A new date selection was made: " +
          start.format("YYYY-MM-DD") +
          "  -  " +
          start +
          " to " +
          end.format("YYYY-MM-DD")
      );
      $("[name='daterange']").val(
        `${start.format("MM/DD/YYYY")} - ${end.format("MM/DD/YYYY")}`
      );

      renderLoadingTable("#filteredData table", 12);
      var formData = $("#filterForm").serialize();

      updateUrlParams(formData);
      fetch_data(formData);
    }
  );

  // Handle form input changes
  $("#filterForm input, #filterForm select")
    .off("change keyup")
    .on(
      "change keyup",
      debounce(function (event) {
        var $this = $(this);

        // If the input has the class 'only-keypress', skip change event
        if ($this.attr("type") === "text" && event.type !== "keyup") {
          return;
        }

        renderLoadingTable("#filteredData table", 12);
        var formData = $("#filterForm").serialize();
        updateUrlParams(formData);
        fetch_data(formData);
      }, 300)
    );

  // Handle pagination
  $(document).on("click", "#paginationLinks a", function (e) {
    renderLoadingTable("#filteredData table", 12);
    e.preventDefault();
    var page = $(this).attr("href").split("page=")[1];
    var formData = $("#filterForm").serialize() + "&page=" + page;
    updateUrlParams(formData);
    fetch_data(formData);
  });
  $(document).on("change", "#per_page ", function (e) {
    renderLoadingTable("#filteredData table", 12);
    e.preventDefault();
    var page = $(this).val();
    var formData = $("#filterForm").serialize() + "&per_page=" + page;
    updateUrlParams(formData);
    fetch_data(formData);
  });

  // Fetch data with AJAX
  function fetch_data(formData) {
    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      success: function (data) {
        $("#" + appenddiv).html(data);
        $(".selectWithoutAjax").select2();
        // Reinitialize Daterangepicker after AJAX content is loaded
        initializeDaterangepicker();
      },
      error: function (xhr, status, error) {
        console.error(error);
        handleAjaxError(xhr, status, error);

        // Swal.fire({
        //   icon: "error",
        //   title: "Error",
        //   text: "Something went wrong: " + xhr.status + " " + error,
        //   confirmButtonColor: "#3085d6",
        // });
      },
    });
  }

  // // Update URL parameters
  // function updateUrlParams(formData) {
  //   const urlParams = new URLSearchParams(formData);
  //   const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
  //   window.history.pushState(null, "", newUrl);
  // }

  // Update URL parameters without duplicates
  function updateUrlParamsbk(formData) {
    const urlParams = new URLSearchParams(window.location.search);
    const newParams = new URLSearchParams(formData); // Serialized data

    // Merge newParams into urlParams
    for (const [key, value] of newParams) {
      if (value) {
        urlParams.set(key, value);
      } else {
        urlParams.delete(key);
      }
    }

    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
    window.history.pushState(null, "", newUrl);
  }



function updateUrlParams(formData) {
    const urlParams = new URLSearchParams(window.location.search);
    const newParams = new URLSearchParams(formData);

        for (const [key, value] of newParams) {
       
                urlParams.delete(key);
            
        
    }
    // Handle array parameters like commodity_id[]
    for (const [key, value] of newParams) {
        if (key.endsWith('[]')) {
            if (value) {
                // Add new value to array parameter
                urlParams.append(key, value);
            }
        } else {
            if (value) {
                urlParams.set(key, value);
            } else {
                urlParams.delete(key);
            }
        }
    }
    
    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
    window.history.pushState(null, "", newUrl);
}
  
  // // Load filter values from URL on page load
  // function loadFiltersFromUrl() {
  //   const urlParams = new URLSearchParams(window.location.search);
  //   urlParams.forEach((value, key) => {
  //     const $field = $(`[name="${key}"]`);
  //     if ($field.length) {
  //       if ($field.is(":checkbox")) {
  //         $field.prop("checked", value === "true");
  //       } else if ($field.is(":radio")) {
  //         $field.filter(`[value="${value}"]`).prop("checked", true);
  //       } else {
  //         $field.val(value).trigger("change");
  //       }
  //     }
  //   });
  // }

  // Initialize filters on page load
  // loadFiltersFromUrl();
  fetch_data($("#filterForm").serialize());

  // Initialize Daterangepicker
  function initializeDaterangepicker() {
    try {
      if ($("#date_range").length) {
        var currentDate = moment().add(1, "days");
        var startDate = moment().subtract(28, "days");

        $("#date_range").daterangepicker({
          startDate: startDate,
          endDate: currentDate,
          autoUpdateInput: false,
          locale: {
            cancelLabel: "Clear Date & All",
          },
        });

        $("#date_range").val(
          startDate.format("YYYY-MM-DD") +
            " - " +
            currentDate.format("YYYY-MM-DD")
        );

        $("#date_range").on("apply.daterangepicker", function (ev, picker) {
          $(this).val(
            picker.startDate.format("YYYY-MM-DD") +
              " - " +
              picker.endDate.format("YYYY-MM-DD")
          );
          var formData = $("#filterForm").serialize();
          updateUrlParams(formData);
          fetch_data(formData);
        });

        $("#date_range").on("cancel.daterangepicker", function (ev, picker) {
          $(this).val("");
          var formData = $("#filterForm").serialize();
          updateUrlParams(formData);
          fetch_data(formData);
        });
      }
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: "error",
        title: "Initialization Error",
        text:
          "An error occurred while initializing the date range picker: " +
          error.message,
        confirmButtonColor: "#3085d6",
      });
    }
  }
}

if (!SUBMISSION_ON_ENTER) {
  $(document).on("keypress", "#ajaxSubmit input", function (e) {
    if (e.which === 13) {
      e.preventDefault();
      return false;
    }
  });
}

$(document).on("submit", "#ajaxSubmit", function (e) {

  var formhunyr = $(this);

  e.preventDefault(); // Avoid executing the actual submit of the form.

  // Clear previous errors and success messages
  $(".print-error-msg").find("ul").html("");
  $(".alert-success").find("ul").html("");

  var form = $(this);
  var actionUrl = form.attr("action");

  var formData = new FormData(form[0]);


 // Find which submit button was actually clicked
 var clickedSubmit = $(document.activeElement);
 if (clickedSubmit.is('input[type="submit"], button[type="submit"]')) {
   var submitName = clickedSubmit.attr("name");
   var submitValue = clickedSubmit.val() || clickedSubmit.text() || "";
   
   if (submitName) {
     formData.append(submitName, submitValue);
   }
 }




  // Process 'notes' field if value is '1'
  var notesValue = formData.get("notes");
  if (notesValue == 1) {
    var editorElement = document.querySelector(".ql-editor");
    var textContent = editorElement.innerHTML;
    formData.append("notes", textContent);
  }

  // Remove any previous error styling and messages
  $(".error-message").remove();
  $(".is-invalid").removeClass("is-invalid");

  // Display SweetAlert loader
  Swal.fire({
    title: "Processing",
    text: "Please wait...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  // AJAX request
  $.ajax({
    type: "POST",
    url: actionUrl,
    data: formData,
    processData: false,
    contentType: false,

    success: function (data) {
      Swal.close();

      if (data.catchError) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.catchError,
          confirmButtonColor: "#D95000",
        });
      } else if ($.isEmptyObject(data.error)) {
        Swal.fire({
          icon: "success",
          title: "Success",
          text: data.success,
          confirmButtonColor: "#3085d6",
        }).then((result) => {
          // if (result.isConfirmed) {
          if (form.data("reset") === true) {
            form[0].reset();
          }

          var url = form.find("#url").val();
          var listRefresh = form.find("#listRefresh").val();
          var ajaxLoadFlag = form.find("#ajaxLoadFlag").val();
          $(formhunyr).parents(".modal-sidebar").removeClass("open");
          $(".main-content").css("cursor", "auto");

          var afterAjaxElement = form.find("#afterAjax");
          if (afterAjaxElement.length > 0) {
            var variableName = afterAjaxElement.data("variable");

            if (
              data.data &&
              data.data[variableName] &&
              data.data[variableName].id
            ) {
              var originalOnClick = afterAjaxElement.attr("onclick");
              var routeMatch = originalOnClick.match(/'([^']+)'/);

              if (routeMatch && routeMatch[1]) {
                var originalRoute = routeMatch[1];
                var newId = data.data[variableName].id;

                var newRoute = originalRoute.replace(
                  /\/(\d+)(\/edit)?$/,
                  "/" + newId + "$2"
                );

                if (newRoute !== originalRoute) {
                  var newOnClick = originalOnClick.replace(
                    originalRoute,
                    newRoute
                  );
                  afterAjaxElement.attr("onclick", newOnClick);
                  afterAjaxElement.trigger("click");
                } else {
                  console.error(
                    "Route replacement failed - routes are identical"
                  );
                }
              }
            }
          }

          if (url) {
            window.location.href = url;
          }
          if (listRefresh) {
            filterationCommon(listRefresh);
            $(formhunyr).parents(".model").slideUp();
          }
          if (ajaxLoadFlag == 1) {
            getAjaxDataOnEditColumns();
          }
          // }
        });
      } else {
        printErrorMsg(data.error);
      }
    },

    error: function (xhr, status, error) {
  Swal.close();

  if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
    let errors = xhr.responseJSON.errors;
    let message = "";

    for (let field in errors) {
      message += errors[field].join("<br>") + "<br>";
    }

    Swal.fire({
      title: "Validation Error",
      html: message,
      icon: "warning",
      confirmButtonColor: "#D95000",
    });

    printErrorMsg(errors);
  }

  else if (xhr.responseJSON && xhr.responseJSON.message) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: xhr.responseJSON.message,
      confirmButtonColor: "#D95000",
    });
  }

  else {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: xhr.responseText || "Something went wrong. Please try again.",
      confirmButtonColor: "#D95000",
    });
  }
},
  });
});

function validateSlabInput(input) {
  const maxRange = parseFloat(input.dataset.maxRange) || 100;
  const isPercentage = input.dataset.isPercentage;
  const value = parseFloat(input.value) || 0;

  input.classList.remove("warning", "danger");

  if (value > maxRange && value <= 100) {
    input.classList.add("warning");
  } else if (value > 100) {
    if (isPercentage) {
      input.value = 100;
    }
    input.classList.add("warning");
    // input.classList.add("danger");
  }
}

function validateDropdown(dropdown) {
  const defaultValue = dropdown.dataset.defaultValue;
  const currentValue = dropdown.value;

  dropdown.classList.remove("warning", "danger");

  if (currentValue !== defaultValue) {
    dropdown.classList.add("warning");
  }
}

function validateInput(input) {
  const defaultValue = input.dataset.defaultValue || "";
  const currentValue = input.value.trim();
  input.classList.remove("warning", "danger");
  if (currentValue !== defaultValue) {
    input.classList.add("warning");
  }
}

function printErrorMsg(errors) {
  // Clear previous errors
  $(".print-error-msg").find("ul").html("");
  $(".print-error-msg").css("display", "block");
  $(".is-invalid").removeClass("is-invalid");
  $(".error-message").remove();

  // Process each error
  $.each(errors, function (key, messages) {
    // Check if this is an array field error (contains a dot and a number)
    if (key.match(/^(.+)\.(\d+)$/)) {
      // Extract the base field name and index
      var matches = key.match(/^(.+)\.(\d+)$/);
      var fieldName = matches[1];
      var index = parseInt(matches[2]);

      // Find the corresponding field in the form
      // For array fields, we need to find the field at the specific index
      var field = $("[name='" + fieldName + "[]']").eq(index);

      if (field.length) {
        field.addClass("is-invalid");

        // Add error message after the field
        if (field.hasClass("select2")) {
          field
            .parent()
            .find(".select2-container")
            .after(
              '<div class="error-message text-danger">' + messages[0] + "</div>"
            );
        } else {
          field
            .parents(".form-group")
            .append(
              '<div class="error-message text-danger">' + messages[0] + "</div>"
            );
        }
      }

      // Also add to the error message list
      $(".print-error-msg")
        .find("ul")
        .append("<li>" + messages[0] + "</li>");
    } else {
      // Regular field (non-array)
      var fields = $("[name='" + key + "']");

      fields.each(function () {
        var field = $(this);
        field.addClass("is-invalid");

        if (field.hasClass("select2")) {
          field
            .parent()
            .find(".select2-container")
            .after(
              '<div class="error-message text-danger">' + messages[0] + "</div>"
            );
        } else {
          field
            .parents(".form-group")
            .append(
              '<div class="error-message text-danger">' + messages[0] + "</div>"
            );
        }
      });

      // Add to the error message list
      $(".print-error-msg")
        .find("ul")
        .append("<li>" + messages[0] + "</li>");
    }
  });
}
function openImageModal(
  imageUrls,
  title = "Image Viewer",
  drawerWidth = "50%"
) {
  $("#modal-sidebar .modal-title").html(title);
  $("#modal-sidebar").css("width", drawerWidth).css("right", `-${drawerWidth}`);

  $("#modal-sidebar .modal-tab-content").html(`
    <div class="image-viewer-container" style="height: 100%; overflow-y: auto;">
      <div class="image-loader text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2">Loading images...</p>
      </div>
    </div>
  `);

  $("#modal-sidebar").addClass("open");
  $("body").addClass("drawer-opened");

  const images = Array.isArray(imageUrls) ? imageUrls : [imageUrls];
  const container = $("#modal-sidebar .image-viewer-container");
  let loadedImages = 0;

  images.forEach((imageUrl, index) => {
    const img = new Image();
    img.onload = function () {
      loadedImages++;

      const imageElement = $(`
        <div class="image-wrapper mb-4" style="text-align: center;">
          <img src="${imageUrl}" class="img-fluid" style="max-height: 70vh; max-width: 100%;">
          ${
            images.length > 1
              ? `<div class="image-counter mt-2">Image ${index + 1} of ${
                  images.length
                }</div>`
              : ""
          }
          <div class="image-actions mt-2">
            <button class="btn btn-sm btn-primary zoom-in" data-image="${imageUrl}">
              <i class="ft-plus"></i> Zoom In
            </button>
            <button class="btn btn-sm btn-primary zoom-out" data-image="${imageUrl}">
              <i class="ft-minus"></i> Zoom Out
            </button>
            <button class="btn btn-sm btn-primary zoom-reset" data-image="${imageUrl}">
              <i class="ft-refresh-cw"></i> Reset
            </button>
          </div>
        </div>
      `);

      container.find(".image-loader").before(imageElement);

      if (loadedImages === images.length) {
        container.find(".image-loader").remove();
      }
    };

    img.onerror = function () {
      loadedImages++;
      container.find(".image-loader").before(`
        <div class="alert alert-danger">
          Failed to load image: ${imageUrl}
        </div>
      `);

      if (loadedImages === images.length) {
        container.find(".image-loader").remove();
      }
    };

    img.src = imageUrl;
  });

  $(document).on("click", ".zoom-in", function () {
    const img = $(this).closest(".image-wrapper").find("img");
    const currentWidth = img.width();
    img.css("width", currentWidth * 1.2);
  });

  $(document).on("click", ".zoom-out", function () {
    const img = $(this).closest(".image-wrapper").find("img");
    const currentWidth = img.width();
    img.css("width", currentWidth * 0.8);
  });

  $(document).on("click", ".zoom-reset", function () {
    $(this).closest(".image-wrapper").find("img").css("width", "");
  });
}

function openModal(button, url, title, viewonly = false, drawerWidth = "50%") {
  
  var $button = $(button); // Get the button element
  var originalText = $button.html(); // Store the original button text
  $button
    .prop("disabled", true)
    .html(
      `<span class="spinnerforajax"><span class="spinner-grow spinner-border-sm" role="status" aria-hidden="true"></span></span> ${originalText}`
    );

  $("#modal-sidebar .modal-title").html(title);
  // $('#settings').modal('show');
  var thisModel = $("#settinsgs");
  $("body").on("click", '[data-close="model"]', function () {
    $(thisModel).hide();
  });
  $("#modal-sidebar .modal-body").html("");
  $(".loader-container").show();

  // Use jQuery AJAX to fetch modal content
  $.ajax({
    url: url,
    method: "GET",
    success: function (data) {
      if (drawerWidth) {
        $("#modal-sidebar").css("width", drawerWidth);
        $("#modal-sidebar").css("right", `-${drawerWidth}`);
      }
    //  $('[data-toggle="tooltip"]').tooltip();
      $("#modal-sidebar").addClass("open");
      $("body").addClass("drawer-opened");
    
      // Inject modal content into the page
      $("#modal-sidebar .modal-tab-content").html(data);
      $('[data-toggle="tooltip"]').tooltip();
      //  initTinyMCE();
      if (viewonly) {
        $("#modal-sidebar :input").prop("readonly", true);
        $("#modal-sidebar select").prop("disabled", true);
        $("#modal-sidebar textarea").prop("readonly", true);
        $("#modal-sidebar :checkbox").prop("disabled", true);
        $("#modal-sidebar :file").prop("disabled", true);
        $('#modal-sidebar [type="submit"]').remove();
        // tinymce.editors.forEach(function (editor) {
        //   editor.setMode("readonly");
        // });
      }
      $(".loader-container").hide();
      $button.prop("disabled", false).html(originalText); // Reset button
    },
    error: function (xhr, status, error) {
      handleAjaxError(xhr, status, error);
      $(".loader-container").hide();
      $button.prop("disabled", false).html(originalText); // Reset button
    },
  });
}

//Handles Errors
function handleAjaxError(xhr, status, error) {
  console.error("Error loading content:", status);
  console.error("Error loading content:", xhr.status);

  // Extract error message and details
  var errorMessage =
    xhr.responseJSON && xhr.responseJSON.message
      ? xhr.responseJSON.message
      : "An unexpected error occurred.";
  var errorDetails =
    xhr.responseJSON && xhr.responseJSON.details
      ? xhr.responseJSON.details
      : error;

  // Handle specific HTTP status codes
  if (xhr.status === 401) {
    Swal.fire({
      icon: "warning",
      title: "Session Expired",
      text: "Your session has expired. Please log in again.",
    }).then(() => {
      window.location.href = loginUrl;
    });
    return;
  } else if (xhr.status === 403) {
    Swal.fire({
      icon: "error",
      title: "Access Denied",
      text: "You do not have permission to perform this action.",
    });
  } else if (xhr.status === 404) {
    Swal.fire({
      icon: "error",
      title: "Not Found",
      text: "The requested resource could not be found.",
    });
  } else if (xhr.status === 500) {
    Swal.fire({
      icon: "error",
      title: "Server Error",
      text: "An internal server error occurred. Please try again later.",
    });
  } else if (status === "timeout") {
    Swal.fire({
      icon: "warning",
      title: "Timeout",
      text: "The request timed out. Please check your internet connection and try again.",
    });
  } else if (status === "error" && xhr.status === 0) {
    Swal.fire({
      icon: "error",
      title: "Internet Disconnected",
      text: "It seems you are not connected to the internet. Please check your connection.",
    });
  } else {
    Swal.fire({
      icon: "error",
      title: "Error",
      html: `<p>Error Code ${xhr.status}: ${errorMessage}</p><small>${errorDetails}</small>`,
    });
  }
}

function renderLoadingTable(tableId, rows) {
  // Select the table by ID
  const table = $(tableId);
  if (table.length === 0) {
    console.error("Table not found!");
    return;
  }

  // Find thead and count columns
  const thead = table.find("thead");
  if (thead.length === 0) {
    console.error("Thead not found! Please define <thead> with columns.");
    return;
  }

  const columns = thead.find("th").length; // Count the <th> elements
  if (columns === 0) {
    console.error("No columns found in <thead>!");
    return;
  }

  // Clear tbody if it exists
  let tbody = table.find("tbody");
  if (tbody.length === 0) {
    tbody = $("<tbody class='shimmer-table'></tbody>");
    table.append(tbody);
  } else {
    tbody.empty();
    tbody.addClass("shimmer-table");
  }

  // Generate rows and cells with loading-shimmer
  for (let i = 0; i < rows; i++) {
    const tr = $("<tr></tr>");
    for (let j = 0; j < columns; j++) {
      // const td = $(`<td colspan="${columns}">-<span class="loading-shimmer">Loading</span></td>`); // Add shimmer class
      const td = $(`<td >-<span class="loading-shimmer">Loading</span></td>`);
      tr.append(td);
    }
    tbody.append(tr);
  }
}

$(document).ready(function () {
  const body = $("body");
  const switchElement = $("#color-switch-1");

  // Check initial state from Laravel rendered class
  if (switchElement.is(":checked")) {
    body.addClass("layout-dark");
  }

  // On switch toggle
  switchElement.on("change", function () {
    if ($(this).is(":checked")) {
      body.addClass("layout-dark");
      saveTheme("dark");
    } else {
      body.removeClass("layout-dark");
      saveTheme("light");
    }
  });

  // Function to save the theme in cookies
  function saveTheme(theme) {
    $.ajax({
      url: "/set-layout-cookie",
      method: "POST",
      data: { layout: theme },
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
      },
      success: function () {
        console.log("Cookie saved successfully");
      },
      error: function () {
        console.error("Failed to save cookie");
      },
    });
  }
});

// function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {
//   $(selector).select2({
//       ajax: {
//           url: '/dynamic-fetch-data', // Your dynamic route
//           type: 'GET',
//           dataType: 'json',
//           delay: 250,
//           data: function(params) {
//               return {
//                   search: params.term, // Search term
//                   table: tableName, // Dynamic table name
//                   column: columnName, // Dynamic column name
//                   idColumn: idColumn, // Dynamic column for ID
//                   enableTags: enableTags // Enable tag creation
//               };
//           },
//           processResults: function(data) {
//               return {
//                   results: data.items // Data from the server
//               };
//           }
//       },
//       minimumInputLength: 3, // Search after 3 characters
//       tags: enableTags, // Enable tag creation if no result found
//       multiple: isMultiple // Allow multiple selections
//   });
// }

function initializeDynamicSelect2(
  selector,
  tableName,
  columnName,
  idColumn = "id",
  enableTags = false,
  isMultiple = false,
  isSelectOnClose = true,
  isAllowClear = false
) {
  const $el = $(selector);

  $el.select2({
    ajax: {
      url: "/dynamic-fetch-data",
      type: "GET",
      dataType: "json",
      delay: 250,
      data: function (params) {
        return {
          search: params.term || "",
          table: tableName,
          column: columnName,
          idColumn: idColumn,
          enableTags: enableTags,
        };
      },
      processResults: function (data) {
        // Manually insert the null/placeholder option at the beginning
        const items = isAllowClear
          ? [{ id: "all", text: "Select an option" }, ...data.items]
          : data.items;

        return {
          results: items,
        };
      },
    },
    minimumInputLength: 0,
    tags: enableTags,
    multiple: isMultiple,
    allowClear: isAllowClear,
    selectOnClose: isSelectOnClose,
    placeholder: "Select an option",
  });

  // Set the placeholder as selected by default
  // $el.val('').trigger('change');

  // Ensure placeholder remains selected when cleared
  $el.on("select2:clear", function () {
    console.log("dd");
    const newOption = new Option("Select an option", "", true, true);
    $(this).append(newOption).trigger("change");
  });

  // Optional: If you want to prevent selecting the placeholder option
  $el.on("select2:select", function (e) {
    if (e.params.data.id === "all") {
      $(this).val("").trigger("change");
    }
  });
}
function initializeDynamicDependentSelect2(
  selector,
  target,
  tableName,
  columnName,
  idColumn = "id",
  targetTable = null,
  targetColumn = null,
  targetDisplayColumn = "name",
  enableTags = false,
  isMultiple = false,
  isSelectOnClose = true,
  isAllowClear = false
) {
  const $el = $(selector);
  const $targetEl = $(target);

  $targetEl.select2({
    ajax: {
      url: "/dynamic-dependent-fetch-data",
      dataType: "json",
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          table: targetTable,
          column: targetDisplayColumn,
          idColumn: "id",
          targetTable: targetTable,
          targetColumn: targetColumn,
          fetchMode: "target",
          sourceId: $el.val(),
        };
      },
      processResults: function (data) {
        return {
          results: data.items,
        };
      },
    },
    minimumInputLength: 0,
    allowClear: true,
    placeholder: "Select options",
  });

  $el.select2({
    ajax: {
      url: "/dynamic-dependent-fetch-data",
      type: "GET",
      dataType: "json",
      delay: 250,
      data: function (params) {
        return {
          search: params.term || "",
          table: tableName,
          column: columnName,
          idColumn: idColumn,
          enableTags: enableTags,
          targetTable: targetTable,
          targetColumn: targetColumn,
          fetchMode: "source",
        };
      },
      processResults: function (data) {
        const items = isAllowClear
          ? [{ id: "all", text: "Select an option" }, ...data.items]
          : data.items;

        return {
          results: items,
        };
      },
    },
    minimumInputLength: 0,
    tags: enableTags,
    multiple: isMultiple,
    allowClear: isAllowClear,
    selectOnClose: isSelectOnClose,
    placeholder: "Select an option",
  });

  $el.on("change", function () {
    const selectedId = $(this).val();
    $targetEl.val(null).trigger("change");

    if (selectedId && selectedId !== "all") {
      $targetEl.select2("open");

      $.ajax({
        url: "/dynamic-dependent-fetch-data",
        data: {
          table: targetTable,
          column: targetDisplayColumn,
          fetchMode: "target",
          sourceId: selectedId,
        },
        success: function (data) {
          const options = data.items.map(
            (item) => new Option(item.text, item.id, true, true)
          );
          $targetEl.empty().append(options).trigger("change");
        },
      });
    }
  });

  $el.on("select2:clear", function () {
    $targetEl.val(null).trigger("change");
    const newOption = new Option("Select an option", "", true, true);
    $(this).append(newOption).trigger("change");
  });

  $el.on("select2:select", function (e) {
    if (e.params.data.id === "all") {
      $(this).val("").trigger("change");
    }
  });
}

function initializeDynamicDependentCall1Select2(
  selector,
  target,
  tableName,
  columnName,
  idColumn = "id",
  targetTable = null,
  targetColumn = null,
  targetDisplayColumn = "name",
  enableTags = false,
  isMultiple = false,
  isSelectOnClose = true,
  isAllowClear = false,
  extraFilters = {}
) {
  const $el = $(selector);
  const $targetEl = $(target);

  $targetEl.select2({
    ajax: {
      url: "/dynamic-dependent-fetch-data",
      dataType: "json",
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          table: targetTable,
          column: targetDisplayColumn,
          idColumn: "id",
          targetTable: targetTable,
          targetColumn: targetColumn,
          fetchMode: "target",
          sourceId: $el.val(),
          ...extraFilters
        };
      },
      processResults: function (data) {
        return {
          results: data.items,
        };
      },
    },
    minimumInputLength: 0,
    allowClear: true,
    placeholder: "Select options",
  });

}

// Handle select change event
function handleSelectChange(selectElement) {
  const selectedValue = selectElement.value;
  console.log("Selected Value:", selectedValue);
  // You can add additional logic here if needed
}

// Initialize the Select2 on page load
// $(document).ready(function() {
//   initializeDynamicSelect2('#dynamicSelect2', 'users', 'name', true, true);
// });

$(document).ready(function () {
  $("body").on("change", "#imageUpload", function (event) {
    var file = event.target.files[0]; // Get the selected file
    if (file) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $("#imagePreview").css(
          "background-image",
          "url(" + e.target.result + ")"
        ); // Set the blob URL
      };
      reader.readAsDataURL(file); // Read the file as a Data URL
    }
  });
});

(function (window, undefined) {
  "use strict";

  /*
  NOTE:
  ------
  PLACE HERE YOUR OWN JAVASCRIPT CODE IF NEEDED
  WE WILL RELEASE FUTURE UPDATES SO IN ORDER TO NOT OVERWRITE YOUR JAVASCRIPT CODE PLEASE CONSIDER WRITING YOUR SCRIPT HERE.  */
})(window);
