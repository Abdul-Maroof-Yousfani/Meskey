$.ajaxSetup({
  headers: {
    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
  },
});

// generalize submit
// $(document).on("submit", "#subm", function (e) {
//   e.preventDefault(); // avoid to execute the actual submit of the form.
//
//   $(".print-error-msg").find("ul").html("");
//   $(".alert-success").find("ul").html("");
//
//   var form = $(this);
//   var actionUrl = form.attr("action");
//   var formData = new FormData(form[0]);
//   var notesValue = formData.get("notes");
//   if (notesValue == 1) {
//     console.log(notesValue);
//     var editorElement = document.querySelector(".ql-editor");
//     var textContent = editorElement.innerHTML;
//     var notes_data = textContent;
//     formData.append("notes", notes_data);
//   }
//   $('.error-message').remove();
//   $('.is-invalid').removeClass('is-invalid');
//   $(".loader-container").show();
//   $.ajax({
//     type: "POST",
//     url: actionUrl,
//     data: formData, // serializes the form's elements.
//     processData: false, // Important: Prevent jQuery from processing the data.
//     contentType: false,
//
//     success: function (data) {
//       console.log(data.data);
//       $(".loader-container").hide();
//       console.log(data.error);
//
//       if (data.catchError) {
//         var successMessage = form.closest("body").find(".alert-success");
//         successMessage.addClass("hide");
//         $(".alert-danger").removeClass("hide");
//         $(".print-error-msg").find("ul").html("");
//         $(".print-error-msg").css("display", "block");
//         $(".print-error-msg")
//           .find("ul")
//           .append("<li>" + data.catchError + "</li>");
//       }
//       if ($.isEmptyObject(data.error)) {
//         var successMessage = form.closest("body").find(".alert-success");
//         successMessage.removeClass("hide");
//         successMessage.html("<ul><li>" + data.success + "</li></ul>");
//         $("#subm").trigger("reset");
//         var url = form.find("#url").val();
//         var listRefresh = form.find("#listRefresh").val();
//         var ajaxLoadFlag = form.find("#ajaxLoadFlag").val();
//
//         if (url != undefined) {
//           window.location.href = url;
//         }
//         if (listRefresh != undefined) {
//           filterationCommon(listRefresh)
//         }
//         if (ajaxLoadFlag == 1) {
//           getAjaxDataOnEditColumns();
//         }
//       } else {
//         printErrorMsg(data.error);
//       }
//     },
//     error: function (xhr, status, error) {
//       $(".loader-container").hide();
//       console.log(error);
//
//       if (xhr.responseJSON && xhr.responseJSON.errors) {
//         var validationErrors = xhr.responseJSON.errors;
//
//         $.each(validationErrors, function (key, errors) {
//           var field = $('[name="' + key + '"]');
//           field.addClass('is-invalid');
//
//           // For Select2, place the error after the closest parent container
//           if (field.hasClass('select2')) {
//             field.parent().find('.select2-container').after('<div class="error-message text-danger">' + errors[0] + '</div>');
//           } else {
//             field.after('<div class="error-message text-danger">' + errors[0] + '</div>');
//           }
//         });
//
//         Swal.fire({
//           title: 'Validation Errors',
//           text: 'Some fields are mandatory. Please check and correct the errors.',
//           icon: 'error',
//           confirmButtonColor: '#D95000'
//         });
//       } else if (xhr.responseJSON && xhr.responseJSON.message) {
//         Swal.fire({
//           icon: 'error',
//           title: 'Error',
//           text: xhr.responseJSON.message,
//           confirmButtonColor: '#D95000'
//         });
//       } else {
//         Swal.fire({
//           icon: 'error',
//           title: 'Error',
//           text: xhr.responseText,
//           confirmButtonColor: '#D95000'
//         });
//       }
//     },
//   });
// });

$(document).on("submit", "#subm", function (e) {
  var formhunyr = $(this);
  e.preventDefault(); // Avoid executing the actual submit of the form.

  // Clear previous errors and success messages
  $(".print-error-msg").find("ul").html("");
  $(".alert-success").find("ul").html("");

  var form = $(this);
  var actionUrl = form.attr("action");

  if (uploadedFileIds !== "undefined" && uploadedFileIds.length) {
    document.getElementById("uploaded_file_ids").value = uploadedFileIds
      .flat()
      .join(",");
  }
  var formData = new FormData(form[0]);

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
      Swal.close(); // Close loader

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
          if (result.isConfirmed) {
            // Further actions after the user clicks "Okay"
            if (form.data("reset") === true) {
              form[0].reset(); // Reset the form without trigger
            }
            // Handle redirection or list refresh
            var url = form.find("#url").val();
            var listRefresh = form.find("#listRefresh").val();
            var ajaxLoadFlag = form.find("#ajaxLoadFlag").val();
            $(formhunyr).parents(".model").slideUp();
            console.log(listRefresh);

            if (url) {
              window.location.href = url;
            }
            if (listRefresh) {
              console.log({ listRefresh });
              filterationCommon(listRefresh);
              $(formhunyr).parents(".model").slideUp();
            }
            if (ajaxLoadFlag == 1) {
              getAjaxDataOnEditColumns();
            }
          }
        });
      } else {
        printErrorMsg(data.error);
      }
    },

    error: function (xhr, status, error) {
      Swal.close(); // Close loader

      if (xhr.responseJSON && xhr.responseJSON.errors) {
        var validationErrors = xhr.responseJSON.errors;

        $.each(validationErrors, function (key, errors) {
          var field = $('[name="' + key + '"]');
          field.addClass("is-invalid");

          // For Select2, place the error after the closest parent container
          if (field.hasClass("select2")) {
            field
              .parent()
              .find(".select2-container")
              .after(
                '<div class="error-message text-danger">' + errors[0] + "</div>"
              );
          } else {
            field.after(
              '<div class="error-message text-danger">' + errors[0] + "</div>"
            );
          }
        });

        Swal.fire({
          title: "Validation Errors",
          text: "Some fields are mandatory. Please check and correct the errors.",
          icon: "error",
          confirmButtonColor: "#D95000",
        });
      } else if (xhr.responseJSON && xhr.responseJSON.message) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: xhr.responseJSON.message,
          confirmButtonColor: "#D95000",
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: xhr.responseText,
          confirmButtonColor: "#D95000",
        });
      }
    },
  });
});

$(document).on("submit", "#quick_add", function (e) {
  e.preventDefault(); // avoid to execute the actual submit of the form.
  var validate = validation();

  if (validate == false) {
    return;
  }
  $(".print-error-msg").find("ul").html("");
  $(".alert-success").find("ul").html("");

  var form = $(this);
  var actionUrl = form.attr("action");
  var formData = new FormData(form[0]);
  var notesValue = formData.get("notes");
  if (notesValue == 1) {
    console.log(notesValue);
    var editorElement = document.querySelector(".ql-editor");
    var textContent = editorElement.innerHTML;
    var notes_data = textContent;
    formData.append("notes", notes_data);
  }
  $(".loader-container").show();
  $.ajax({
    type: "POST",
    url: actionUrl,
    data: formData, // serializes the form's elements.
    processData: false, // Important: Prevent jQuery from processing the data.
    contentType: false,

    success: function (data) {
      console.log(data.data);
      $(".loader-container").hide();
      console.log(data.error);

      if (data.catchError) {
        var successMessage = form.closest("body").find(".alert-success");
        successMessage.addClass("hide");
        $(".alert-danger").removeClass("hide");
        $(".print-error-msg").find("ul").html("");
        $(".print-error-msg").css("display", "block");
        $(".print-error-msg")
          .find("ul")
          .append("<li>" + data.catchError + "</li>");
      }
      if ($.isEmptyObject(data.error)) {
        var successMessage = form.closest("body").find(".alert-success");
        successMessage.removeClass("hide");
        successMessage.html("<ul><li>" + data.success + "</li></ul>");
        $("#quick_add").trigger("reset");
        var url = form.find("#url").val();
        var ajaxLoadFlag = form.find("#ajaxLoadFlag").val();

        if (url != undefined) {
          window.location.href = url;
        } else {
          var appendaction = form.parents(".model").find("#appendClass").val();
          console.log(appendaction);
          $("." + appendaction).append(
            '<option value="' +
              data.data.id +
              '">' +
              data.data.name +
              "</option>"
          );
          form.parents("#modal2").hide();
        }
        if (ajaxLoadFlag == 1) {
          getAjaxDataOnEditColumns();
        }
      } else {
        printErrorMsg(data.error);
      }
    },
    error: function (xhr, status, error) {
      $(".loader-container").hide();
      console.log(error);

      if (xhr.responseJSON && xhr.responseJSON.errors) {
        var validationErrors = xhr.responseJSON.errors;

        // Display validation errors
        $(".alert-danger").removeClass("hide");
        $(".print-error-msg").find("ul").html("");
        $(".print-error-msg").css("display", "block");

        $.each(validationErrors, function (key, errors) {
          $.each(errors, function (index, error) {
            $(".print-error-msg")
              .find("ul")
              .append("<li>" + error + "</li>");
          });
        });
      } else {
        console.log(xhr.responseText);
      }
    },
  });
});

function previewImage(event) {
  var fileInput = event.target;
  var imagePreview = document.getElementById("account-upload-img");
  // Check if a file is selected
  if (fileInput.files.length > 0) {
    var file = fileInput.files[0];

    // Check if the selected file is an image
    if (file.type.startsWith("image/")) {
      var reader = new FileReader();

      reader.onload = function (e) {
        // Display the image preview
        imagePreview.src = e.target.result;
      };

      reader.readAsDataURL(file);
    } else {
      imagePreview.src = "#"; // Clear the image preview
    }
  } else {
    // Clear the image preview if no file is selected
    imagePreview.src = "#";
  }
}

//email submit
$(document).on("submit", "#submitEmail", function (e) {
  e.preventDefault(); // avoid to execute the actual submit of the form.
  var validate = validation();

  if (validate == false) {
    return;
  }
  $(".print-error-msg").find("ul").html("");
  $(".alert-success").find("ul").html("");

  var form = $(this);
  var actionUrl = form.attr("action");
  var formData = new FormData(form[0]);
  $(".loader-container").show();

  $.ajax({
    type: "POST",
    url: actionUrl,
    data: formData, // serializes the form's elements.
    processData: false, // Important: Prevent jQuery from processing the data.
    contentType: false,

    success: function (data) {
      $(".loader-container").hide();

      if (data.catchError) {
        $(".alert-danger").removeClass("hide");
        $(".print-error-msg").find("ul").html("");
        $(".print-error-msg").css("display", "block");
        $(".print-error-msg")
          .find("ul")
          .append("<li>" + data.catchError + "</li>");
      }
      if ($.isEmptyObject(data.error)) {
        var successMessage = form.find(".alert-success");
        successMessage.removeClass("hide");
        successMessage.html(data.success);
        $("#submEmail").trigger("reset");
        var url = form.find("#url").val();

        if (url != undefined) {
          window.location.href = url;
        }
      } else {
        printErrorMsg(data.error);
      }
    },
    error: function (xhr, status, error) {
      $(".loader-container").hide();
      console.log(error);

      if (xhr.responseJSON && xhr.responseJSON.errors) {
        var validationErrors = xhr.responseJSON.errors;

        // Display validation errors
        $(".alert-danger").removeClass("hide");
        $(".print-error-msg").find("ul").html("");
        $(".print-error-msg").css("display", "block");

        $.each(validationErrors, function (key, errors) {
          $.each(errors, function (index, error) {
            $(".print-error-msg")
              .find("ul")
              .append("<li>" + error + "</li>");
          });
        });
      } else {
        console.log(xhr.responseText);
      }
    },
  });
});

function printErrorMsg(msg) {
  $(".print-error-msg").find("ul").html("");
  $(".print-error-msg").css("display", "block");
  $.each(msg, function (key, value) {
    $(".print-error-msg")
      .find("ul")
      .append("<li>" + value + "</li>");
    window.scrollTo(0, 0);
  });
}

function validation() {
  var required = document.getElementsByClassName("required");

  for (i = 0; i < required.length; i++) {
    var rf = required[i].id;

    if ($("#" + rf).val() == "") {
      $("#" + rf).css("border-color", "red");
      $("#" + rf).focus();
      validate = 1;
      //	alert(rf + ' ' + 'Required');
      event.preventDefault();
      return false;
    } else {
      $("#" + rf).css("border-color", "#ccc");
      validate = 0;
    }
  }
}

function validateForm() {
  var newPassword = document.getElementById("account-new-password").value;
  var retypePassword = document.getElementById("aconfirm-new-password").value;
  var passwordError = document.getElementById("passwordError");

  if (newPassword.length < 8) {
    passwordError.innerHTML =
      "New password must be at least 8 characters long.";
    return false;
  }

  if (newPassword !== retypePassword) {
    passwordError.innerHTML = "Passwords do not match.";
    return false;
  }

  // Clear any previous error messages
  passwordError.innerHTML = "";

  // If everything is valid, allow the form to be submitted
  return true;
}

function resetForm() {
  document.getElementById("account-upload-img").src = "#"; // Clear the image preview
}
