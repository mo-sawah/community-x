/**
 * Community X Public JavaScript
 *
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Wait for document ready
  $(document).ready(function () {
    CommunityXPublic.init();
  });

  /**
   * Main Public Object
   */
  window.CommunityXPublic = {
    /**
     * Initialize public functionality
     */
    init: function () {
      this.bindEvents();
      this.initModals();
      this.initFollowSystem();
      this.initSearch();
      this.initFormValidation();
      this.initMemberCards();
      this.initProfileCards();
      this.initAutoComplete();
    },

    /**
     * Bind event handlers
     */
    bindEvents: function () {
      var self = this;

      // Follow/Unfollow buttons
      $(document).on("click", ".follow-btn", function (e) {
        e.preventDefault();
        self.followUser($(this));
      });

      $(document).on("click", ".unfollow-btn", function (e) {
        e.preventDefault();
        self.unfollowUser($(this));
      });

      // Profile edit buttons
      $(document).on("click", ".edit-profile-btn", function (e) {
        e.preventDefault();
        self.openProfileEditModal();
      });

      // Modal triggers
      $(document).on("click", "[data-modal]", function (e) {
        e.preventDefault();
        var modalId = $(this).data("modal");
        self.openModal(modalId);
      });

      // Search filters auto-submit
      $(
        '.members-filter-form select, .members-filter-form input[name="location"]'
      ).on("change", function () {
        $(this).closest("form").submit();
      });

      // Search input delay
      var searchTimeout;
      $(".members-search-input").on("input", function () {
        var $form = $(this).closest("form");
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(function () {
          $form.submit();
        }, 500);
      });

      // User dropdown toggle
      $(".user-menu").on("click", function (e) {
        e.preventDefault();
        $(this).find(".user-dropdown").toggle();
      });

      // Close dropdowns when clicking outside
      $(document).on("click", function (e) {
        if (!$(e.target).closest(".user-menu").length) {
          $(".user-dropdown").hide();
        }
      });

      // Member card hover effects
      $(".member-card, .member-card-compact").hover(
        function () {
          $(this).addClass("hover-effect");
        },
        function () {
          $(this).removeClass("hover-effect");
        }
      );

      // Skill tag clicks
      $(document).on("click", ".skill-tag:not(.more)", function () {
        var skill = $(this).text();
        var currentUrl = new URL(window.location);
        currentUrl.searchParams.set("skills", skill);
        window.location.href = currentUrl.toString();
      });

      // Load more members
      $(".members-load-more").on("click", function (e) {
        e.preventDefault();
        self.loadMoreMembers($(this));
      });
    },

    /**
     * Initialize modal functionality
     */
    initModals: function () {
      var self = this;

      // Close modal handlers
      $(document).on("click", ".modal-close, .community-modal", function (e) {
        if (e.target === this) {
          self.closeModal();
        }
      });

      // Escape key to close modal
      $(document).on("keydown", function (e) {
        if (e.keyCode === 27) {
          // Escape key
          self.closeModal();
        }
      });
    },

    /**
     * Initialize follow system
     */
    initFollowSystem: function () {
      // Update follow button states on page load
      this.updateFollowButtonStates();
    },

    /**
     * Initialize search functionality
     */
    initSearch: function () {
      // Search suggestions (placeholder for future enhancement)
      $(".search-input, .members-search-input").on("focus", function () {
        // Could add search suggestions dropdown here
      });
    },

    /**
     * Initialize form validation
     */
    initFormValidation: function () {
      var self = this;

      // Real-time validation for registration form
      $(".community-registration-form").each(function () {
        var $form = $(this);

        // Username validation
        $form.find('[name="username"]').on("blur", function () {
          self.validateUsername($(this));
        });

        // Email validation
        $form.find('[name="email"]').on("blur", function () {
          self.validateEmail($(this));
        });

        // Password strength
        $form.find('[name="password"]').on("input", function () {
          self.checkPasswordStrength($(this));
        });

        // Password confirmation
        $form.find('[name="confirm_password"]').on("input", function () {
          self.validatePasswordConfirmation($(this));
        });
      });

      // Profile edit form
      $("#edit-profile-form").on("submit", function (e) {
        e.preventDefault();
        self.submitProfileForm($(this));
      });
    },

    /**
     * Initialize member cards
     */
    initMemberCards: function () {
      // Lazy load member avatars
      $(".member-avatar").each(function () {
        var $img = $(this);
        $img.on("load", function () {
          $(this).addClass("loaded");
        });
      });

      // Member card animations
      $(".member-card, .member-card-compact").each(function (index) {
        $(this).css("animation-delay", index * 0.1 + "s");
        $(this).addClass("fade-in");
      });
    },

    /**
     * Initialize profile cards
     */
    initProfileCards: function () {
      // Profile avatar change
      $(".edit-avatar-btn").on("click", function (e) {
        e.preventDefault();
        // This will be enhanced with file upload in future updates
        alert("Avatar upload will be available in a future update.");
      });
    },

    /**
     * Initialize autocomplete functionality
     */
    initAutoComplete: function () {
      // Skills autocomplete (basic implementation)
      var availableSkills = [];

      // Collect existing skills for autocomplete
      $(".skill-tag").each(function () {
        var skill = $(this)
          .text()
          .replace(/\+\d+.*/, "")
          .trim();
        if (skill && availableSkills.indexOf(skill) === -1) {
          availableSkills.push(skill);
        }
      });

      // Simple autocomplete for skills input
      $('[name="skills"]').on("input", function () {
        var input = $(this).val();
        var lastComma = input.lastIndexOf(",");
        var currentSkill = input.substring(lastComma + 1).trim();

        if (currentSkill.length >= 2) {
          // Basic matching - could be enhanced with a proper autocomplete library
          var matches = availableSkills.filter(function (skill) {
            return skill.toLowerCase().includes(currentSkill.toLowerCase());
          });

          // Could display suggestions here
        }
      });
    },

    /**
     * Follow user
     */
    followUser: function ($button) {
      var userId = $button.data("user-id");
      var originalText = $button.html();

      $button
        .prop("disabled", true)
        .html('<span class="spinner"></span> Following...');

      $.post(community_x_ajax.ajax_url, {
        action: "community_x_follow_user",
        user_id: userId,
        nonce: community_x_ajax.nonce,
      })
        .done(
          function (response) {
            if (response.success) {
              $button
                .removeClass("follow-btn btn-primary")
                .addClass("unfollow-btn btn-secondary")
                .html('<i class="fas fa-user-check"></i> Following');

              // Update follower count if visible
              this.updateFollowerCount(userId, 1);

              // Show success message
              this.showNotification(response.data, "success");
            } else {
              this.showNotification(
                response.data || "Failed to follow user",
                "error"
              );
            }
          }.bind(this)
        )
        .fail(
          function () {
            this.showNotification("Network error. Please try again.", "error");
          }.bind(this)
        )
        .always(function () {
          $button.prop("disabled", false);
        });
    },

    /**
     * Unfollow user
     */
    unfollowUser: function ($button) {
      var userId = $button.data("user-id");

      $button
        .prop("disabled", true)
        .html('<span class="spinner"></span> Unfollowing...');

      $.post(community_x_ajax.ajax_url, {
        action: "community_x_unfollow_user",
        user_id: userId,
        nonce: community_x_ajax.nonce,
      })
        .done(
          function (response) {
            if (response.success) {
              $button
                .removeClass("unfollow-btn btn-secondary")
                .addClass("follow-btn btn-primary")
                .html('<i class="fas fa-user-plus"></i> Follow');

              // Update follower count if visible
              this.updateFollowerCount(userId, -1);

              // Show success message
              this.showNotification(response.data, "success");
            } else {
              this.showNotification(
                response.data || "Failed to unfollow user",
                "error"
              );
            }
          }.bind(this)
        )
        .fail(
          function () {
            this.showNotification("Network error. Please try again.", "error");
          }.bind(this)
        )
        .always(function () {
          $button.prop("disabled", false);
        });
    },

    /**
     * Update follower count
     */
    updateFollowerCount: function (userId, change) {
      $(".stat-item").each(function () {
        var $stat = $(this);
        if (
          $stat.find(".stat-label").text().toLowerCase().includes("followers")
        ) {
          var $number = $stat.find(".stat-number");
          var current = parseInt($number.text().replace(/[^\d]/g, ""));
          var newCount = Math.max(0, current + change);
          $number.text(newCount.toLocaleString());
        }
      });
    },

    /**
     * Update follow button states
     */
    updateFollowButtonStates: function () {
      // This could be enhanced to check follow status on page load
    },

    /**
     * Open modal
     */
    openModal: function (modalId) {
      $("#" + modalId)
        .show()
        .addClass("fade-in");
      $("body").addClass("modal-open");
    },

    /**
     * Open profile edit modal
     */
    openProfileEditModal: function () {
      this.openModal("edit-profile-modal");
    },

    /**
     * Close modal
     */
    closeModal: function () {
      $(".community-modal").hide().removeClass("fade-in");
      $("body").removeClass("modal-open");
    },

    /**
     * Submit profile form
     */
    submitProfileForm: function ($form) {
      var $button = $form.find('button[type="submit"]');
      var originalText = $button.html();

      $button
        .prop("disabled", true)
        .html('<span class="spinner"></span> Saving...');

      // Prepare form data
      var formData = {
        action: "community_x_update_profile",
        nonce: community_x_ajax.nonce,
        profile_data: {
          bio: $form.find('[name="bio"]').val(),
          location: $form.find('[name="location"]').val(),
          website: $form.find('[name="website"]').val(),
          social_links: {},
          skills: $form
            .find('[name="skills"]')
            .val()
            .split(",")
            .map(function (s) {
              return s.trim();
            })
            .filter(function (s) {
              return s;
            }),
          interests: $form
            .find('[name="interests"]')
            .val()
            .split(",")
            .map(function (s) {
              return s.trim();
            })
            .filter(function (s) {
              return s;
            }),
          is_public: $form.find('[name="is_public"]').is(":checked") ? 1 : 0,
        },
      };

      // Get social links
      $form.find('[name^="social_links"]').each(function () {
        var platform = $(this)
          .attr("name")
          .match(/\[(.*?)\]/)[1];
        var url = $(this).val();
        if (url) {
          formData.profile_data.social_links[platform] = url;
        }
      });

      $.post(community_x_ajax.ajax_url, formData)
        .done(
          function (response) {
            if (response.success) {
              this.showNotification("Profile updated successfully!", "success");
              this.closeModal();

              // Refresh page to show updated data
              setTimeout(function () {
                location.reload();
              }, 1000);
            } else {
              this.showNotification(
                response.data || "Failed to update profile",
                "error"
              );
            }
          }.bind(this)
        )
        .fail(
          function () {
            this.showNotification("Network error. Please try again.", "error");
          }.bind(this)
        )
        .always(function () {
          $button.prop("disabled", false).html(originalText);
        });
    },

    /**
     * Load more members
     */
    loadMoreMembers: function ($button) {
      var originalText = $button.html();
      $button
        .html('<span class="spinner"></span> Loading...')
        .prop("disabled", true);

      // This is a placeholder - would be implemented with actual pagination
      setTimeout(
        function () {
          $button.html(originalText).prop("disabled", false);
          this.showNotification(
            "Load more functionality will be enhanced in future updates.",
            "info"
          );
        }.bind(this),
        1000
      );
    },

    /**
     * Validate username
     */
    validateUsername: function ($input) {
      var username = $input.val().trim();

      if (username.length < 3) {
        this.showFieldError(
          $input,
          "Username must be at least 3 characters long"
        );
        return false;
      }

      // Check availability via AJAX
      $.post(community_x_ajax.ajax_url, {
        action: "community_x_check_username",
        username: username,
        nonce: community_x_ajax.nonce,
      }).done(
        function (response) {
          if (response.success) {
            this.showFieldSuccess($input, "Username is available");
          } else {
            this.showFieldError(
              $input,
              response.data || "Username is not available"
            );
          }
        }.bind(this)
      );

      return true;
    },

    /**
     * Validate email
     */
    validateEmail: function ($input) {
      var email = $input.val().trim();
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailRegex.test(email)) {
        this.showFieldError($input, "Please enter a valid email address");
        return false;
      }

      this.clearFieldValidation($input);
      return true;
    },

    /**
     * Check password strength
     */
    checkPasswordStrength: function ($input) {
      var password = $input.val();
      var strength = 0;

      if (password.length >= 6) strength++;
      if (password.match(/[a-z]/)) strength++;
      if (password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;

      var strengthText = "";
      var strengthClass = "";

      switch (strength) {
        case 0:
        case 1:
          strengthText = "Weak";
          strengthClass = "weak";
          break;
        case 2:
        case 3:
          strengthText = "Medium";
          strengthClass = "medium";
          break;
        case 4:
        case 5:
          strengthText = "Strong";
          strengthClass = "strong";
          break;
      }

      var $indicator = $input.siblings(".password-strength");
      if (!$indicator.length) {
        $indicator = $('<div class="password-strength"></div>');
        $input.after($indicator);
      }

      $indicator.removeClass("weak medium strong").addClass(strengthClass);
      $indicator.text("Password strength: " + strengthText);
    },

    /**
     * Validate password confirmation
     */
    validatePasswordConfirmation: function ($input) {
      var password = $('[name="password"]').val();
      var confirmPassword = $input.val();

      var $feedback = $input.siblings(".password-match-feedback");
      if (!$feedback.length) {
        $feedback = $('<div class="password-match-feedback"></div>');
        $input.after($feedback);
      }

      if (confirmPassword && confirmPassword !== password) {
        $feedback.removeClass("match").addClass("no-match");
        $feedback.text("Passwords do not match");
        return false;
      } else if (confirmPassword && confirmPassword === password) {
        $feedback.removeClass("no-match").addClass("match");
        $feedback.text("Passwords match");
        return true;
      } else {
        $feedback.removeClass("match no-match").text("");
        return false;
      }
    },

    /**
     * Show field error
     */
    showFieldError: function ($field, message) {
      this.clearFieldValidation($field);

      var $error = $('<div class="field-error">').text(message).css({
        color: "#ef4444",
        fontSize: "0.75rem",
        marginTop: "0.25rem",
      });

      $field.after($error).addClass("error");
    },

    /**
     * Show field success
     */
    showFieldSuccess: function ($field, message) {
      this.clearFieldValidation($field);

      var $success = $('<div class="field-success">').text(message).css({
        color: "#10b981",
        fontSize: "0.75rem",
        marginTop: "0.25rem",
      });

      $field.after($success).addClass("success");
    },

    /**
     * Clear field validation
     */
    clearFieldValidation: function ($field) {
      $field.siblings(".field-error, .field-success").remove();
      $field.removeClass("error success");
    },

    /**
     * Show notification
     */
    showNotification: function (message, type) {
      type = type || "info";

      var $notification = $('<div class="community-notification">')
        .addClass("notification-" + type)
        .text(message)
        .css({
          position: "fixed",
          top: "20px",
          right: "20px",
          background: this.getNotificationColor(type),
          color: "white",
          padding: "1rem 1.5rem",
          borderRadius: "var(--radius)",
          boxShadow: "var(--shadow-lg)",
          zIndex: 1000,
          maxWidth: "300px",
        });

      $("body").append($notification);

      // Animate in
      $notification
        .css({
          opacity: 0,
          transform: "translateX(100%)",
        })
        .animate(
          {
            opacity: 1,
            transform: "translateX(0)",
          },
          300
        );

      // Auto-remove after 5 seconds
      setTimeout(function () {
        $notification.animate(
          {
            opacity: 0,
            transform: "translateX(100%)",
          },
          300,
          function () {
            $(this).remove();
          }
        );
      }, 5000);

      // Click to dismiss
      $notification.on("click", function () {
        $(this).animate(
          {
            opacity: 0,
            transform: "translateX(100%)",
          },
          300,
          function () {
            $(this).remove();
          }
        );
      });
    },

    /**
     * Get notification color based on type
     */
    getNotificationColor: function (type) {
      switch (type) {
        case "success":
          return "#10b981";
        case "error":
          return "#ef4444";
        case "warning":
          return "#f59e0b";
        case "info":
        default:
          return "#3b82f6";
      }
    },

    /**
     * Debounce function for performance
     */
    debounce: function (func, wait, immediate) {
      var timeout;
      return function () {
        var context = this;
        var args = arguments;
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

    /**
     * Utility: Format number with commas
     */
    formatNumber: function (num) {
      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    /**
     * Utility: Get time ago string
     */
    timeAgo: function (date) {
      var now = new Date();
      var diffMs = now - new Date(date);
      var diffSec = Math.floor(diffMs / 1000);
      var diffMin = Math.floor(diffSec / 60);
      var diffHour = Math.floor(diffMin / 60);
      var diffDay = Math.floor(diffHour / 24);

      if (diffSec < 60) {
        return "just now";
      } else if (diffMin < 60) {
        return diffMin + " minute" + (diffMin === 1 ? "" : "s") + " ago";
      } else if (diffHour < 24) {
        return diffHour + " hour" + (diffHour === 1 ? "" : "s") + " ago";
      } else if (diffDay < 7) {
        return diffDay + " day" + (diffDay === 1 ? "" : "s") + " ago";
      } else {
        return new Date(date).toLocaleDateString();
      }
    },

    /**
     * Utility: Smooth scroll to element
     */
    scrollTo: function (element, offset) {
      offset = offset || 0;
      var $element = $(element);

      if ($element.length) {
        $("html, body").animate(
          {
            scrollTop: $element.offset().top - offset,
          },
          500
        );
      }
    },

    /**
     * Utility: Check if element is in viewport
     */
    isInViewport: function (element) {
      var $element = $(element);
      var elementTop = $element.offset().top;
      var elementBottom = elementTop + $element.outerHeight();
      var viewportTop = $(window).scrollTop();
      var viewportBottom = viewportTop + $(window).height();

      return elementBottom > viewportTop && elementTop < viewportBottom;
    },

    /**
     * Initialize lazy loading for images
     */
    initLazyLoading: function () {
      var lazyImages = $(".lazy-load");

      if ("IntersectionObserver" in window) {
        var imageObserver = new IntersectionObserver(function (
          entries,
          observer
        ) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              var image = entry.target;
              image.src = image.dataset.src;
              image.classList.remove("lazy-load");
              imageObserver.unobserve(image);
            }
          });
        });

        lazyImages.each(function () {
          imageObserver.observe(this);
        });
      } else {
        // Fallback for browsers without IntersectionObserver
        lazyImages.each(function () {
          this.src = this.dataset.src;
          $(this).removeClass("lazy-load");
        });
      }
    },

    /**
     * Handle infinite scroll
     */
    initInfiniteScroll: function (container, callback) {
      var $container = $(container);
      var loading = false;

      $(window).on(
        "scroll",
        this.debounce(function () {
          if (loading) return;

          var scrollTop = $(window).scrollTop();
          var windowHeight = $(window).height();
          var documentHeight = $(document).height();

          // Load more when user is 200px from bottom
          if (scrollTop + windowHeight >= documentHeight - 200) {
            loading = true;
            callback().always(function () {
              loading = false;
            });
          }
        }, 100)
      );
    },
  };

  // Initialize when DOM is ready
  $(document).ready(function () {
    // Add CSS for body when modal is open
    $("<style>")
      .prop("type", "text/css")
      .html(
        `
            body.modal-open {
                overflow: hidden;
            }
            
            .community-notification {
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .community-notification:hover {
                transform: translateX(-5px) !important;
            }
            
            .member-card.hover-effect,
            .member-card-compact.hover-effect {
                transform: translateY(-2px);
                box-shadow: var(--shadow-lg);
            }
            
            .form-control.error {
                border-color: #ef4444;
                box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
            }
            
            .form-control.success {
                border-color: #10b981;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            }
            
            .spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid transparent;
                border-top: 2px solid currentColor;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .fade-in {
                animation: fadeIn 0.3s ease-out;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `
      )
      .appendTo("head");
  });
})(jQuery);

// Additional utility functions outside the main object
window.CommunityXUtils = {
  /**
   * Copy text to clipboard
   */
  copyToClipboard: function (text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function () {
        CommunityXPublic.showNotification("Copied to clipboard!", "success");
      });
    } else {
      // Fallback for older browsers
      var textArea = document.createElement("textarea");
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand("copy");
      document.body.removeChild(textArea);
      CommunityXPublic.showNotification("Copied to clipboard!", "success");
    }
  },

  /**
   * Share URL via Web Share API or fallback
   */
  shareUrl: function (url, title, text) {
    if (navigator.share) {
      navigator.share({
        title: title,
        text: text,
        url: url,
      });
    } else {
      // Fallback - copy to clipboard
      this.copyToClipboard(url);
    }
  },

  /**
   * Detect device type
   */
  isMobile: function () {
    return window.innerWidth <= 768;
  },

  /**
   * Get browser info
   */
  getBrowser: function () {
    var ua = navigator.userAgent;
    var browser = "Unknown";

    if (ua.includes("Chrome")) browser = "Chrome";
    else if (ua.includes("Firefox")) browser = "Firefox";
    else if (ua.includes("Safari")) browser = "Safari";
    else if (ua.includes("Edge")) browser = "Edge";

    return browser;
  },
};

// Enhanced interactions for Phase 4
jQuery(document).ready(function ($) {
  // Like/Unlike posts
  $(document).on("click", ".like-btn", function (e) {
    e.preventDefault();

    if (!community_x_ajax.user_logged_in) {
      CommunityXPublic.showNotification(
        "Please log in to like posts",
        "warning"
      );
      return;
    }

    var $btn = $(this);
    var postId = $btn.data("post-id");
    var $icon = $btn.find("i");
    var $count = $btn.find("span");
    var currentCount = parseInt($count.text()) || 0;
    var isLiked = $btn.hasClass("liked");

    // Optimistic UI update
    if (isLiked) {
      $btn.removeClass("liked");
      $icon.removeClass("fas").addClass("far");
      $count.text(Math.max(0, currentCount - 1));
    } else {
      $btn.addClass("liked");
      $icon.removeClass("far").addClass("fas");
      $count.text(currentCount + 1);
    }

    $btn.prop("disabled", true);

    $.post(community_x_ajax.ajax_url, {
      action: "community_x_like_post",
      post_id: postId,
      nonce: community_x_ajax.nonce,
    })
      .done(function (response) {
        if (response.success) {
          CommunityXPublic.showNotification(response.data.message, "success");
          // Update all like buttons for this post
          $('.like-btn[data-post-id="' + postId + '"]').each(function () {
            var $otherBtn = $(this);
            if (response.data.action === "liked") {
              $otherBtn.addClass("liked");
              $otherBtn.find("i").removeClass("far").addClass("fas");
            } else {
              $otherBtn.removeClass("liked");
              $otherBtn.find("i").removeClass("fas").addClass("far");
            }
          });
        } else {
          // Revert optimistic update on error
          if (!isLiked) {
            $btn.removeClass("liked");
            $icon.removeClass("fas").addClass("far");
            $count.text(currentCount);
          } else {
            $btn.addClass("liked");
            $icon.removeClass("far").addClass("fas");
            $count.text(currentCount);
          }
          CommunityXPublic.showNotification(
            "Failed to update like status",
            "error"
          );
        }
      })
      .fail(function () {
        // Revert optimistic update on error
        if (!isLiked) {
          $btn.removeClass("liked");
          $icon.removeClass("fas").addClass("far");
          $count.text(currentCount);
        } else {
          $btn.addClass("liked");
          $icon.removeClass("far").addClass("fas");
          $count.text(currentCount);
        }
        CommunityXPublic.showNotification("Network error occurred", "error");
      })
      .always(function () {
        $btn.prop("disabled", false);
      });
  });

  // Bookmark posts
  $(document).on("click", ".bookmark-btn", function (e) {
    e.preventDefault();

    if (!community_x_ajax.user_logged_in) {
      CommunityXPublic.showNotification(
        "Please log in to bookmark posts",
        "warning"
      );
      return;
    }

    var $btn = $(this);
    var postId = $btn.data("post-id");

    $btn.prop("disabled", true).addClass("loading");

    $.post(community_x_ajax.ajax_url, {
      action: "community_x_bookmark_post",
      post_id: postId,
      nonce: community_x_ajax.nonce,
    })
      .done(function (response) {
        if (response.success) {
          $btn.addClass("bookmarked");
          $btn.find("i").removeClass("far").addClass("fas");
          CommunityXPublic.showNotification(response.data.message, "success");
        } else {
          CommunityXPublic.showNotification("Failed to bookmark post", "error");
        }
      })
      .always(function () {
        $btn.prop("disabled", false).removeClass("loading");
      });
  });

  // Advanced search with suggestions
  var searchTimeout;
  var $searchInput = $(".search-input, .members-search-input");
  var $suggestionsContainer = $('<div class="search-suggestions"></div>');

  $searchInput.after($suggestionsContainer);

  $searchInput.on("input", function () {
    var query = $(this).val().trim();
    var $container = $(this).siblings(".search-suggestions");

    clearTimeout(searchTimeout);

    if (query.length < 2) {
      $container.hide().empty();
      return;
    }

    searchTimeout = setTimeout(function () {
      $.post(community_x_ajax.ajax_url, {
        action: "community_x_search_suggestions",
        query: query,
      }).done(function (response) {
        if (response.success && response.data.length > 0) {
          var html = '<div class="suggestions-list">';
          response.data.forEach(function (suggestion) {
            html +=
              '<div class="suggestion-item" data-text="' +
              suggestion.text +
              '">';
            html += '<i class="fas fa-tag"></i>';
            html += "<span>" + suggestion.text + "</span>";
            if (suggestion.count) {
              html += "<small>(" + suggestion.count + ")</small>";
            }
            html += "</div>";
          });
          html += "</div>";

          $container.html(html).show();
        } else {
          $container.hide().empty();
        }
      });
    }, 300);
  });

  // Handle suggestion clicks
  $(document).on("click", ".suggestion-item", function () {
    var text = $(this).data("text");
    var $input = $(this)
      .closest(".search-suggestions")
      .siblings(".search-input, .members-search-input");
    $input.val(text);
    $(this).closest(".search-suggestions").hide();
    $input.closest("form").submit();
  });

  // Hide suggestions when clicking outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".search-input, .search-suggestions").length) {
      $(".search-suggestions").hide();
    }
  });

  // Load more activity feed
  $(document).on("click", ".load-more-activity", function () {
    var $btn = $(this);
    var page = $btn.data("page");
    var originalText = $btn.html();

    $btn
      .html('<i class="fas fa-spinner fa-spin"></i> Loading...')
      .prop("disabled", true);

    $.post(community_x_ajax.ajax_url, {
      action: "community_x_load_activity",
      page: page,
      nonce: community_x_ajax.nonce,
    })
      .done(function (response) {
        if (response.success && response.data.length > 0) {
          var html = "";
          response.data.forEach(function (activity) {
            html += buildActivityHTML(activity);
          });

          $(".activity-feed-list").append(html);
          $btn.data("page", page + 1);

          if (response.data.length < 10) {
            $btn.hide(); // No more items
          }
        } else {
          $btn.hide();
          CommunityXPublic.showNotification(
            "No more activities to load",
            "info"
          );
        }
      })
      .always(function () {
        $btn.html(originalText).prop("disabled", false);
      });
  });

  // Enhanced share functionality
  $(document).on("click", ".share-btn, .share-post-btn", function (e) {
    e.preventDefault();
    var postId = $(this).data("post-id");
    openShareModal(postId);
  });

  function openShareModal(postId) {
    var postUrl = window.location.origin + "/community/post/" + postId + "/";
    var postTitle = $("h1.post-article-title").text() || "Check out this post";

    if (navigator.share) {
      navigator
        .share({
          title: postTitle,
          url: postUrl,
        })
        .catch(console.error);
    } else {
      // Fallback share modal
      var modal = `
                <div class="share-modal-overlay">
                    <div class="share-modal">
                        <div class="share-header">
                            <h3>Share this post</h3>
                            <button class="close-share">&times;</button>
                        </div>
                        <div class="share-options">
                            <button class="share-option" data-share="copy">
                                <i class="fas fa-link"></i>
                                <span>Copy Link</span>
                            </button>
                            <button class="share-option" data-share="twitter">
                                <i class="fab fa-twitter"></i>
                                <span>Twitter</span>
                            </button>
                            <button class="share-option" data-share="facebook">
                                <i class="fab fa-facebook"></i>
                                <span>Facebook</span>
                            </button>
                            <button class="share-option" data-share="linkedin">
                                <i class="fab fa-linkedin"></i>
                                <span>LinkedIn</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;

      $("body").append(modal);

      $('.share-option[data-share="copy"]').on("click", function () {
        navigator.clipboard.writeText(postUrl).then(function () {
          CommunityXPublic.showNotification(
            "Link copied to clipboard!",
            "success"
          );
          $(".share-modal-overlay").remove();
        });
      });

      $('.share-option[data-share="twitter"]').on("click", function () {
        window.open(
          "https://twitter.com/intent/tweet?url=" +
            encodeURIComponent(postUrl) +
            "&text=" +
            encodeURIComponent(postTitle)
        );
        $(".share-modal-overlay").remove();
      });

      $('.share-option[data-share="facebook"]').on("click", function () {
        window.open(
          "https://www.facebook.com/sharer/sharer.php?u=" +
            encodeURIComponent(postUrl)
        );
        $(".share-modal-overlay").remove();
      });

      $('.share-option[data-share="linkedin"]').on("click", function () {
        window.open(
          "https://www.linkedin.com/sharing/share-offsite/?url=" +
            encodeURIComponent(postUrl)
        );
        $(".share-modal-overlay").remove();
      });

      $(".close-share, .share-modal-overlay").on("click", function (e) {
        if (e.target === this) {
          $(".share-modal-overlay").remove();
        }
      });
    }
  }

  function buildActivityHTML(activity) {
    var actionText = "";
    var actionIcon = "";

    switch (activity.action) {
      case "post_created":
        actionIcon = "fas fa-plus-circle";
        actionText = "created a new post";
        break;
      case "post_liked":
        actionIcon = "fas fa-heart";
        actionText = "liked a post";
        break;
      case "user_followed":
        actionIcon = "fas fa-user-plus";
        actionText = "followed someone";
        break;
    }

    var html =
      '<div class="activity-feed-item" data-activity-id="' + activity.id + '">';
    html += '<div class="activity-avatar">';
    html +=
      '<img src="' +
      activity.avatar_url +
      '" alt="' +
      activity.user_name +
      '" />';
    html += "</div>";
    html += '<div class="activity-content">';
    html += '<div class="activity-header">';
    html +=
      '<a href="/community/member/' +
      activity.user_login +
      '/" class="activity-user">' +
      activity.user_name +
      "</a>";
    html +=
      '<span class="activity-action"><i class="' +
      actionIcon +
      '"></i> ' +
      actionText +
      "</span>";
    html += '<span class="activity-time">' + activity.time_ago + "</span>";
    html += "</div>";

    if (activity.post_title) {
      html += '<div class="activity-object">';
      html +=
        '<a href="/community/post/' +
        activity.post_id +
        '/" class="activity-post-link">';
      html += '<i class="fas fa-file-alt"></i>' + activity.post_title;
      html += "</a></div>";
    }

    html += "</div></div>";

    return html;
  }
});
