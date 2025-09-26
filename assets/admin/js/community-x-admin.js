/**
 * Community X Admin JavaScript
 *
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Wait for document ready
  $(document).ready(function () {
    // Initialize admin functionality
    CommunityXAdmin.init();
  });

  /**
   * Main Admin Object
   */
  window.CommunityXAdmin = {
    /**
     * Initialize admin functionality
     */
    init: function () {
      this.bindEvents();
      this.initTooltips();
      this.initConfirmDialogs();
      this.initAjaxForms();
      this.initStatCards();
      this.initSettingsValidation();
    },

    /**
     * Bind event handlers
     */
    bindEvents: function () {
      var self = this;

      // Settings form submission
      $(".community-x-settings form").on("submit", function (e) {
        return self.validateSettingsForm(this);
      });

      // Quick action buttons
      $(".quick-action-btn").on("click", function () {
        $(this).addClass("loading");
      });

      // Stat card hover effects
      $(".stat-card")
        .on("mouseenter", function () {
          $(this).find(".stat-icon").addClass("pulse");
        })
        .on("mouseleave", function () {
          $(this).find(".stat-icon").removeClass("pulse");
        });

      // Toggle switches
      $('.settings-section input[type="checkbox"]').on("change", function () {
        var $this = $(this);
        var $label = $this.closest("label");

        if ($this.is(":checked")) {
          $label.addClass("checked");
        } else {
          $label.removeClass("checked");
        }
      });

      // Tab navigation
      $(".nav-tab").on("click", function (e) {
        e.preventDefault();
        var target = $(this).attr("href");
        self.switchTab(target);
      });

      // Refresh stats button
      $(".refresh-stats").on("click", function (e) {
        e.preventDefault();
        self.refreshStats();
      });

      // Bulk actions
      $(".bulkactions select").on("change", function () {
        var action = $(this).val();
        var $button = $(this).siblings(".button");

        if (action && action !== "-1") {
          $button.prop("disabled", false);
        } else {
          $button.prop("disabled", true);
        }
      });
    },

    /**
     * Initialize tooltips
     */
    initTooltips: function () {
      $("[data-tooltip]").each(function () {
        var $this = $(this);
        var title = $this.attr("data-tooltip");

        $this.attr("title", title).tooltip({
          position: { my: "left+15 center", at: "right center" },
          tooltipClass: "community-x-tooltip",
        });
      });
    },

    /**
     * Initialize confirmation dialogs
     */
    initConfirmDialogs: function () {
      $(".delete-item, .bulk-delete").on("click", function (e) {
        var message =
          $(this).data("confirm") ||
          community_x_admin_ajax.strings.confirm_delete;

        if (!confirm(message)) {
          e.preventDefault();
          return false;
        }
      });
    },

    /**
     * Initialize AJAX forms
     */
    initAjaxForms: function () {
      var self = this;

      $(".ajax-form").on("submit", function (e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('input[type="submit"]');
        var originalText = $button.val();

        // Show loading state
        $button
          .val(community_x_admin_ajax.strings.loading)
          .prop("disabled", true);
        $form.addClass("community-x-loading");

        // Prepare data
        var formData = $form.serialize();
        formData += "&action=community_x_admin_ajax";
        formData += "&nonce=" + community_x_admin_ajax.nonce;

        // Send AJAX request
        $.ajax({
          url: community_x_admin_ajax.ajax_url,
          type: "POST",
          data: formData,
          success: function (response) {
            if (response.success) {
              self.showMessage(
                community_x_admin_ajax.strings.save_success,
                "success"
              );
            } else {
              self.showMessage(
                response.data || community_x_admin_ajax.strings.save_error,
                "error"
              );
            }
          },
          error: function () {
            self.showMessage(
              community_x_admin_ajax.strings.save_error,
              "error"
            );
          },
          complete: function () {
            // Restore button state
            $button.val(originalText).prop("disabled", false);
            $form.removeClass("community-x-loading");
          },
        });
      });
    },

    /**
     * Initialize stat cards animations
     */
    initStatCards: function () {
      // Animate numbers on page load
      $(".stat-card h3").each(function () {
        var $this = $(this);
        var target = parseInt($this.text().replace(/[^0-9]/g, ""));

        if (target > 0) {
          $this.text("0");
          $({ value: 0 }).animate(
            { value: target },
            {
              duration: 1500,
              easing: "easeOutCubic",
              step: function () {
                $this.text(Math.floor(this.value).toLocaleString());
              },
            }
          );
        }
      });
    },

    /**
     * Initialize settings form validation
     */
    initSettingsValidation: function () {
      var self = this;

      // Real-time validation for number inputs
      $('input[type="number"]').on("input", function () {
        var $this = $(this);
        var min = parseInt($this.attr("min"));
        var max = parseInt($this.attr("max"));
        var value = parseInt($this.val());

        if (min && value < min) {
          $this.addClass("error");
          self.showFieldError($this, "Value must be at least " + min);
        } else if (max && value > max) {
          $this.addClass("error");
          self.showFieldError($this, "Value must be no more than " + max);
        } else {
          $this.removeClass("error");
          self.hideFieldError($this);
        }
      });

      // Email validation
      $('input[type="email"]').on("blur", function () {
        var $this = $(this);
        var email = $this.val();

        if (email && !self.isValidEmail(email)) {
          $this.addClass("error");
          self.showFieldError($this, "Please enter a valid email address");
        } else {
          $this.removeClass("error");
          self.hideFieldError($this);
        }
      });
    },

    /**
     * Validate settings form before submission
     */
    validateSettingsForm: function (form) {
      var isValid = true;
      var $form = $(form);

      // Clear previous errors
      $form.find(".field-error").remove();
      $form.find(".error").removeClass("error");

      // Validate required fields
      $form.find("[required]").each(function () {
        var $field = $(this);
        if (!$field.val().trim()) {
          $field.addClass("error");
          this.showFieldError($field, "This field is required");
          isValid = false;
        }
      });

      // Validate posts per page
      var postsPerPage = parseInt(
        $form.find('input[name="community_x_settings[posts_per_page]"]').val()
      );
      if (postsPerPage < 1 || postsPerPage > 50) {
        var $field = $form.find(
          'input[name="community_x_settings[posts_per_page]"]'
        );
        $field.addClass("error");
        this.showFieldError($field, "Posts per page must be between 1 and 50");
        isValid = false;
      }

      if (!isValid) {
        this.showMessage("Please fix the errors below before saving", "error");
        $("html, body").animate(
          {
            scrollTop: $form.find(".error").first().offset().top - 100,
          },
          500
        );
      }

      return isValid;
    },

    /**
     * Switch tab
     */
    switchTab: function (target) {
      // Hide all tab content
      $(".tab-content").hide();
      $(".nav-tab").removeClass("nav-tab-active");

      // Show target tab
      $(target).show();
      $('.nav-tab[href="' + target + '"]').addClass("nav-tab-active");

      // Update URL hash
      if (history.pushState) {
        history.pushState(null, null, target);
      }
    },

    /**
     * Refresh statistics
     */
    refreshStats: function () {
      var self = this;
      var $button = $(".refresh-stats");
      var originalText = $button.text();

      $button
        .html('<i class="fas fa-spinner fa-spin"></i> Refreshing...')
        .prop("disabled", true);

      $.ajax({
        url: community_x_admin_ajax.ajax_url,
        type: "POST",
        data: {
          action: "community_x_refresh_stats",
          nonce: community_x_admin_ajax.nonce,
        },
        success: function (response) {
          if (response.success && response.data) {
            // Update stat cards
            $.each(response.data, function (key, value) {
              $('.stat-card[data-stat="' + key + '"] h3').text(
                value.toLocaleString()
              );
            });

            self.showMessage("Statistics refreshed successfully", "success");
          } else {
            self.showMessage("Failed to refresh statistics", "error");
          }
        },
        error: function () {
          self.showMessage("Failed to refresh statistics", "error");
        },
        complete: function () {
          $button.html(originalText).prop("disabled", false);
        },
      });
    },

    /**
     * Show message to user
     */
    showMessage: function (message, type) {
      type = type || "info";

      var iconClass = {
        success: "fas fa-check-circle",
        error: "fas fa-exclamation-circle",
        warning: "fas fa-exclamation-triangle",
        info: "fas fa-info-circle",
      };

      var $message = $(
        '<div class="notice notice-' +
          type +
          ' is-dismissible community-x-message">'
      )
        .html(
          '<i class="' + iconClass[type] + '"></i> <span>' + message + "</span>"
        )
        .hide();

      // Add to page
      $(".wrap h1").after($message);
      $message.fadeIn();

      // Auto-dismiss after 5 seconds
      setTimeout(function () {
        $message.fadeOut(function () {
          $(this).remove();
        });
      }, 5000);
    },

    /**
     * Show field error
     */
    showFieldError: function ($field, message) {
      this.hideFieldError($field);

      var $error = $('<div class="field-error">')
        .html('<i class="fas fa-exclamation-triangle"></i> ' + message)
        .css({
          color: "#dc2626",
          fontSize: "12px",
          marginTop: "5px",
          display: "flex",
          alignItems: "center",
          gap: "5px",
        });

      $field.after($error);
    },

    /**
     * Hide field error
     */
    hideFieldError: function ($field) {
      $field.siblings(".field-error").remove();
    },

    /**
     * Validate email address
     */
    isValidEmail: function (email) {
      var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    },

    /**
     * Format number with commas
     */
    numberWithCommas: function (x) {
      return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    /**
     * Debounce function for performance
     */
    debounce: function (func, wait, immediate) {
      var timeout;
      return function () {
        var context = this,
          args = arguments;
        var later = function () {
          timeout = null;
          if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
      };
    },
  };

  // Add easing function for animations
  if (typeof jQuery.easing.easeOutCubic === "undefined") {
    jQuery.extend(jQuery.easing, {
      easeOutCubic: function (x, t, b, c, d) {
        return c * (Math.pow(t / d - 1, 3) + 1) + b;
      },
    });
  }

  // Add CSS for pulse animation
  $("<style>")
    .prop("type", "text/css")
    .html(
      `
            .stat-icon.pulse {
                animation: pulse 0.8s ease-in-out;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .community-x-tooltip {
                background: #374151 !important;
                color: #f9fafb !important;
                border: none !important;
                border-radius: 4px !important;
                padding: 8px 12px !important;
                font-size: 12px !important;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            }
        `
    )
    .appendTo("head");
})(jQuery);
