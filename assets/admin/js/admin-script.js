(function ($) {
  $(document).ready(function () {
    // show toast start
    function showToast(config) {
      const { type, timeout, title } = config;

      const icon =
        type === "success"
          ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 16.17l-3.88-3.88L4 13.41l5 5 10-10-1.41-1.42z"/></svg>'
          : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13 3h-2v10h2zm0 14h-2v2h2z"/></svg>';

      const toast = $(`
              <div class="toast ${type}">
                  <div class="header">
                      <span class="icon">${icon}</span>
                      <span>${title}</span>
                      <span class="close-btn">&times;</span>
                  </div>
                  <div class="progress-bar" style="animation-duration: ${timeout}ms"></div>
              </div>
          `);

      $("#toast-container").append(toast);

      // Remove toast on close button click
      toast.find(".close-btn").on("click", function () {
        toast.remove();
      });

      // Auto-remove toast after timeout
      setTimeout(() => {
        toast.remove();
      }, timeout);
    }
    // show toast end

    // tab start
    $(".tab").click(function () {
      // Remove active class from all tabs
      $(".tab").removeClass("active");
      // Add active class to the clicked tab
      $(this).addClass("active");

      // Hide all tab content
      $(".tab-content").hide();
      // Show the content of the clicked tab
      const tabId = $(this).data("tab");
      $("#" + tabId).fadeIn();
    });
    // tab end

    // copy to clipboard start
    $(".copy-button").on("click", function () {
      // Get the path from the second column and construct full URL
      const path = $(this).closest("tr").find("td:nth-child(2)").text();
      const fullUrl = window.location.origin + '/wp-json/api/v1' + path;

      // Use modern clipboard API if available
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(fullUrl).then(function() {
          showToast({
            type: "success",
            timeout: 2000,
            title: "Copied to clipboard",
          });
        }).catch(function(err) {
          console.error('Failed to copy: ', err);
          // Fallback to old method
          fallbackCopyTextToClipboard(fullUrl);
        });
      } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(fullUrl);
      }
    });

    // Fallback copy function for older browsers
    function fallbackCopyTextToClipboard(text) {
      const tempInput = $("<input>");
      $("body").append(tempInput);
      tempInput.val(text).select();

      try {
        document.execCommand("copy");
        showToast({
          type: "success",
          timeout: 2000,
          title: "Copied to clipboard",
        });
      } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        showToast({
          type: "error",
          timeout: 2000,
          title: "Failed to copy to clipboard",
        });
      }

      tempInput.remove();
    }
    // copy to clipboard end

    // run api endpoint start
    $(".run-button").on("click", function () {
      const button = $(this);
      const path = button.closest("tr").find("td:nth-child(2)").text();
      const method = button.closest("tr").find("td:first .api-method").text();
      const fullUrl = window.location.origin + '/wp-json/api/v1' + path;
      const responseContainer = $(".api-response-container");
      const responseStatus = $(".response-status");
      const responseJson = $(".response-json");

      // Prevent multiple clicks
      if (button.hasClass("loading")) {
        return;
      }

      // Add loading state
      button.addClass("loading").text("Running...");
      
      // Show response container
      responseContainer.show();
      
      // Clear previous response
      responseStatus.removeClass("success error").text("Executing API call...");
      responseJson.text("");

      $.ajax({
        type: method,
        url: fullUrl,
        timeout: 10000, // 10 seconds timeout
        success: function (response, textStatus, xhr) {
          // Remove loading state
          button.removeClass("loading").text("Run");
          
          // Show success status
          responseStatus.removeClass("error").addClass("success").text(`Success (${xhr.status})`);
          
          // Format and display response
          let formattedResponse;
          try {
            formattedResponse = JSON.stringify(response, null, 2);
          } catch (e) {
            formattedResponse = response;
          }
          responseJson.text(formattedResponse);
          
          // Show success toast
          showToast({
            type: "success",
            timeout: 3000,
            title: "API call executed successfully",
          });
        },
        error: function (xhr, status, error) {
          // Remove loading state
          button.removeClass("loading").text("Run");
          
          // Show error status
          responseStatus.removeClass("success").addClass("error").text(`Error (${xhr.status})`);
          
          // Format and display error response
          let errorResponse = {
            status: xhr.status,
            statusText: xhr.statusText,
            error: error,
            responseText: xhr.responseText
          };
          
          try {
            const parsedResponse = JSON.parse(xhr.responseText);
            errorResponse.response = parsedResponse;
          } catch (e) {
            // Response is not JSON, keep as text
          }
          
          responseJson.text(JSON.stringify(errorResponse, null, 2));
          
          // Show error toast
          showToast({
            type: "error",
            timeout: 3000,
            title: `API call failed: ${xhr.status} ${xhr.statusText}`,
          });
        }
      });
    });
    // run api endpoint end

    // save credentials start
    $("#save_credentials").on("click", function () {
      const api_url = $("#api_url").val();
      const api_key = $("#api_key").val();

      // add loading spinner
      const loader_button = $(".spinner-loader-wrapper");
      $(loader_button).addClass("loader-spinner");

      $.ajax({
        type: "POST",
        url: wpb_admin_localize.ajax_url,
        data: {
          action: "save_credentials",
          api_url: api_url,
          api_key: api_key,
        },
        success: function (response) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          if (true === response.success) {
            showToast({
              type: "success",
              timeout: 2000,
              title: `${response.data}`,
            });
          } else {
            showToast({
              type: "error",
              timeout: 2000,
              title: `${response.data}`,
            });
          }
        },
        error: function (xhr, status, error) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          showToast({
            type: "error",
            timeout: 2000,
            title: `${response.data}`,
          });
        },
      });
    });
    // save credentials end

    // save options start
    $("#save_options").on("click", function () {
      const option1 = $("#option1").val();
      const option2 = $("#option2").val();

      // add loading spinner
      const loader_button = $(".spinner-loader-wrapper");
      $(loader_button).addClass("loader-spinner");

      $.ajax({
        type: "POST",
        url: wpb_admin_localize.ajax_url,
        data: {
          action: "save_options",
          option1: option1,
          option2: option2,
        },
        success: function (response) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          if (true === response.success) {
            showToast({
              type: "success",
              timeout: 2000,
              title: `${response.data}`,
            });
          } else {
            showToast({
              type: "error",
              timeout: 2000,
              title: `${response.data}`,
            });
          }
        },
        error: function (xhr, status, error) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          showToast({
            type: "error",
            timeout: 2000,
            title: `${response.data}`,
          });
        },
      });
    });
    // save options end
  });
})(jQuery);
