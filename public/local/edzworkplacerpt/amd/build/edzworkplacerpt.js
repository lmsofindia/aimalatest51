// amd/src/edzworkplacerpt.js
define(["jquery", "core/modal", "core/ajax"], function ($, Modal, Ajax) {
  "use strict";

  function updateSendBtn() {
    var checked = $(".edz-row-checkbox:checked").length;
    if (checked > 0) {
      $("#edz_send_message_btn").removeAttr("disabled");
    } else {
      $("#edz_send_message_btn").attr("disabled", "disabled");
    }
  }

  /**
   * Create a basic modal using Modal.create (Moodle 4.3+ API).
   * selected: array of userid ints
   */
  function createMessageModal(selected) {
    // Modal.create accepts modal config (title, body, footer, show, removeOnClose, etc)
    return Modal.create({
      title: "Send message",
      // body can be a string or HTML; we prefer jQuery DOM creation below
      show: false, // we'll call show() after wiring events
      removeOnClose: true, // remove from DOM when closed
    }).then(function (modal) {
      // Build DOM inside the modal root (safer than injecting a string)
      var $root = modal.getRoot();
      var $body = $("<div/>").addClass("edz-modal-body");

      var $subjectGroup = $("<div/>").addClass("form-group");
      $subjectGroup.append(
        $("<label/>").attr("for", "edz_m_subject").text("Subject")
      );
      $subjectGroup.append(
        $("<input/>").attr({
          type: "text",
          id: "edz_m_subject",
          name: "subject",
          class: "form-control",
        })
      );

      var $messageGroup = $("<div/>").addClass("form-group");
      $messageGroup.append(
        $("<label/>").attr("for", "edz_m_body").text("Message (HTML allowed)")
      );
      // Use contenteditable DIV to avoid Moodle editor auto-init problems
      var $editable = $("<div/>")
        .attr({
          id: "edz_m_body",
          contenteditable: "true",
          role: "textbox",
          "aria-multiline": "true",
        })
        .addClass("form-control")
        .css({ "min-height": "160px", "white-space": "pre-wrap" });
      $messageGroup.append($editable);

      var $footer = $("<div/>").addClass("modal-footer");
      $footer.append(
        $("<button/>")
          .attr({
            type: "button",
            id: "edz_cancel",
            class: "btn btn-secondary",
          })
          .text("Cancel")
      );
      $footer.append(
        $("<button/>")
          .attr({ type: "button", id: "edz_send", class: "btn btn-primary" })
          .text("Send")
      );

      $body.append($subjectGroup).append($messageGroup);

      // Insert body and footer to modal
      // modal has methods to set content (older ModalFactory used .setBody, with Modal.create we manipulate DOM)
      // Replace modal body with our content:
      $root.find(".modal-body").empty().append($body);
      // Replace modal footer:
      $root.find(".modal-footer").empty().append($footer);

      // Wire up events
      $root.on("click", "#edz_cancel", function () {
        modal.hide();
      });

      $root.on("click", "#edz_send", function () {
      var subj = $root.find("#edz_m_subject").val() || "";
      var msg = $root.find("#edz_m_body").html() || "";
      subj = subj.trim();
      msg = msg.trim();
      if (!subj || !msg) {
          alert("Please fill subject and message");
          return;
      }

      var call = {
          methodname: "local_edzworkplacerpt_send_message",
          args: { touserids: selected, subject: subj, messagehtml: msg },
      };

      Ajax.call([call])[0]
          .done(function (response) {
              if (response && response.sent) {
                  alert("Messages sent: " + response.sent);
              } else {
                  alert("No messages sent");
              }
              modal.hide();
          })
          .fail(function (err) {
              console.error("Send message failed", err);
              alert("Failed to send message. See console for details.");
          });
  });


      modal.show();
      return modal;
    });
  }

  function init() {
    $(document).ready(function () {
      $(document).on("change", "#edz_select_all", function () {
        $(".edz-row-checkbox").prop("checked", $(this).prop("checked"));
        updateSendBtn();
      });

      $(document).on("change", ".edz-row-checkbox", function () {
        updateSendBtn();
      });

      $(document).on("click", "#edz_send_message_btn", function () {
        var selected = [];
        $(".edz-row-checkbox:checked").each(function () {
          selected.push(parseInt($(this).val(), 10));
        });
        if (selected.length === 0) {
          alert("Please select at least one user.");
          return;
        }
        createMessageModal(selected);
      });

      updateSendBtn();
    });
  }

  return {
    init: init,
  };
});
