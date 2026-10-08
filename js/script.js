document.addEventListener("DOMContentLoaded", function () {
    // Half-open rental intervals: a return at 10:00 frees capacity for a 10:00 start.
    function peakReservedQuantity(intervals, start, end) {
        var events = [];
        (intervals || []).forEach(function (interval) {
            if (!interval) { return; }
            var from = Math.max(start, Number(interval.startTs));
            var to = Math.min(end, Number(interval.endTs));
            var quantity = Math.max(1, Number.parseInt(interval.qty, 10) || 1);
            if (!Number.isFinite(from) || !Number.isFinite(to) || from >= to) { return; }
            events.push({ time: from, delta: quantity }, { time: to, delta: -quantity });
        });
        events.sort(function (a, b) { return a.time - b.time || a.delta - b.delta; });
        var current = 0;
        var peak = 0;
        events.forEach(function (event) {
            current += event.delta;
            peak = Math.max(peak, current);
        });
        return peak;
    }
    var revealItems = document.querySelectorAll(".reveal");
    var toggleButtons = document.querySelectorAll(".toggle-visibility");
    var authSwitchLinks = document.querySelectorAll("[data-auth-switch]");
    var messageUsOpenButtons = document.querySelectorAll("[data-message-us-open]");
    var customerMessageModal = document.querySelector("[data-message-modal]");
    var customerMessageModalCloseButtons = customerMessageModal ? customerMessageModal.querySelectorAll("[data-message-modal-close]") : [];
    var customerMessageForm = customerMessageModal ? customerMessageModal.querySelector("[data-message-form]") : null;
    var customerMessageSubmitButton = customerMessageModal ? customerMessageModal.querySelector("[data-message-submit]") : null;
    var customerMessageFeedback = customerMessageModal ? customerMessageModal.querySelector("[data-message-feedback]") : null;
    var customerMessageAttachmentInput = customerMessageModal ? customerMessageModal.querySelector("[data-message-attachments]") : null;
    var adminNotificationItems = document.querySelectorAll("[data-admin-notification-item]");
    var adminNotificationModal = document.querySelector("[data-admin-notification-modal]");
    var adminNotificationModalCloseButtons = adminNotificationModal ? adminNotificationModal.querySelectorAll("[data-admin-notification-close]") : [];
    var adminNotificationModalTitle = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-title]") : null;
    var adminNotificationModalType = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-type]") : null;
    var adminNotificationModalSender = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-sender]") : null;
    var adminNotificationModalEmail = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-email]") : null;
    var adminNotificationModalTime = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-time]") : null;
    var adminNotificationModalSummary = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-summary]") : null;
    var adminNotificationModalMessage = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-message]") : null;
    var adminNotificationModalAttachments = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-attachments]") : null;
    var adminNotificationModalEmpty = adminNotificationModal ? adminNotificationModal.querySelector("[data-admin-notification-modal-empty]") : null;
    var adminNotificationCountBadges = document.querySelectorAll("[data-admin-notification-count], .topbar-notification-count");
    var adminNotificationTriggers = document.querySelectorAll("[data-admin-notification-trigger]");
    var adminNotificationMarkReadEndpoint = typeof window.__creatyAdminNotificationMarkReadEndpoint === "string"
        ? String(window.__creatyAdminNotificationMarkReadEndpoint || "")
        : "";
    var adminNotificationLiveEndpoint = typeof window.__creatyAdminNotificationLiveEndpoint === "string"
        ? String(window.__creatyAdminNotificationLiveEndpoint || "")
        : "";
    var adminUploadDeliveryReceiptEndpoint = typeof window.__creatyAdminUploadDeliveryReceiptEndpoint === "string"
        ? String(window.__creatyAdminUploadDeliveryReceiptEndpoint || "")
        : "";
    var adminCloseDeliveryLegEndpoint = typeof window.__creatyAdminCloseDeliveryLegEndpoint === "string"
        ? String(window.__creatyAdminCloseDeliveryLegEndpoint || "")
        : "";
    var adminLivePollIntervalMs = 4000;
    var adminLivePollTimerId = null;
    var adminLivePollInFlight = false;
    var adminLiveLastOrderId = "";
    var adminLiveLastUnreadOrderCount = null;
    var adminLiveLastOrdersSignature = typeof window.__creatyAdminBookingsSignature === "string"
        ? String(window.__creatyAdminBookingsSignature || "").trim()
        : "";
    var promoBanner = document.querySelector(".promo-banner");
    var promoCarousel = promoBanner ? promoBanner.querySelector(".promo-carousel") : null;
    var promoPrev = promoBanner ? promoBanner.querySelector(".promo-arrow-left") : null;
    var promoNext = promoBanner ? promoBanner.querySelector(".promo-arrow-right") : null;
    var adminPromoBanner = document.querySelector("[data-admin-promo-banner]");
    var adminPromoRemove = document.querySelector("[data-admin-promo-remove]");
    var adminPromoArchiveEndpoint = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-archive-endpoint") || "") : "";
    var adminPromoRestoreEndpoint = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-restore-endpoint") || "") : "";
    var adminPromoUpdateEndpoint = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-update-endpoint") || "") : "";
    var adminPromoImageBase = adminPromoBanner ? (adminPromoBanner.getAttribute("data-admin-promo-image-base") || "assets/promo_images/") : "assets/promo_images/";
    var adminPromoEditBackdrop = document.querySelector("[data-admin-promo-edit-backdrop]");
    var adminPromoForm = document.querySelector("[data-admin-promo-form]");
    var adminPromoClose = document.querySelector("[data-admin-promo-close]");
    var adminPromoCancel = document.querySelector("[data-admin-promo-cancel]");
    var adminPromoBrowse = document.querySelector("[data-admin-promo-browse]");
    var adminPromoRecrop = document.querySelector("[data-admin-promo-recrop]");
    var adminPromoImageActions = document.querySelector("[data-admin-promo-image-actions]");
    var adminPromoMainActions = document.querySelector("[data-admin-promo-main-actions]");
    var adminPromoCropWorkspace = document.querySelector("[data-admin-promo-crop-workspace]");
    var adminPromoCropCancel = document.querySelector("[data-admin-promo-crop-cancel]");
    var adminPromoCropSave = document.querySelector("[data-admin-promo-crop-save]");
    var adminPromoFileInput = document.querySelector("[data-admin-promo-file]");
    var adminPromoPreviewWrap = document.querySelector("[data-admin-promo-preview-wrap]");
    var adminPromoPreviewImage = document.querySelector("[data-admin-promo-preview-img]");
    var adminPromoZoom = document.querySelector("[data-admin-promo-zoom]");
    var adminPromoSlotNote = document.querySelector("[data-admin-promo-slot-note]");
    var promoIndex = 0;
    var promoTimer = null;
    var promoDelay = 3000;
    var filterNav = document.querySelector(".section-nav-interactive");
    var filterToggles = filterNav ? filterNav.querySelectorAll(".filter-toggle") : [];
    var adminNavBars = document.querySelectorAll("[data-admin-nav]");
    var adminDashboardSettingsOpenButtons = document.querySelectorAll("[data-admin-dashboard-settings-open]");
    var adminBusinessReportPrintButtons = document.querySelectorAll("[data-admin-business-report-print]");
    var adminDashboardSettingsBackdrop = document.querySelector("[data-admin-dashboard-settings-backdrop]");
    var adminDashboardSettingsCloseButtons = adminDashboardSettingsBackdrop ? adminDashboardSettingsBackdrop.querySelectorAll("[data-admin-dashboard-settings-close]") : [];
    var adminDashboardSettingsList = adminDashboardSettingsBackdrop ? adminDashboardSettingsBackdrop.querySelector("[data-admin-dashboard-settings-list]") : null;
    var adminDashboardSettingsResetButton = adminDashboardSettingsBackdrop ? adminDashboardSettingsBackdrop.querySelector("[data-admin-dashboard-settings-reset]") : null;
    var adminDashboardSections = document.querySelectorAll("[data-admin-dashboard-section]");
    var adminBusinessReportPayload = window.__creatyAdminBusinessReport && typeof window.__creatyAdminBusinessReport === "object"
        ? window.__creatyAdminBusinessReport
        : null;
    var adminUsersCreateBackdrop = document.querySelector("[data-admin-users-create-backdrop]");
    var adminUsersOpenModalButtons = document.querySelectorAll("[data-admin-users-open-modal]");
    var adminUsersCloseModalButtons = document.querySelectorAll("[data-admin-users-close-modal]");
    var shouldOpenAdminUsersCreateModal = document.body.getAttribute("data-admin-open-create-user-modal") === "true";
    var adminEquipmentArchiveBackdrop = document.querySelector("[data-admin-equipment-archive-backdrop]");
    var adminEquipmentArchiveOpenButtons = document.querySelectorAll("[data-admin-equipment-archive-open]");
    var adminEquipmentArchiveCloseButtons = document.querySelectorAll("[data-admin-equipment-archive-close]");
    var adminEquipmentStatusBackdrop = document.querySelector("[data-admin-equipment-status-backdrop]");
    var adminEquipmentStatusOpenButtons = document.querySelectorAll("[data-admin-equipment-status-open]");
    var adminEquipmentStatusCloseButtons = document.querySelectorAll("[data-admin-equipment-status-close]");
    var adminEquipmentStatusDeleteButtons = document.querySelectorAll("[data-admin-equipment-status-delete]");
    var adminEquipmentAddButtons = document.querySelectorAll("[data-admin-equipment-add]");
    var adminEquipmentRemoveButtons = document.querySelectorAll("[data-admin-equipment-remove]");
    var adminBookingRows = document.querySelectorAll("[data-admin-booking-row]");
    var adminBookingDetailBackdrop = document.querySelector("[data-admin-booking-detail-backdrop]");
    var adminBookingDetailCloseButtons = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelectorAll("[data-admin-booking-detail-close]") : [];
    var adminBookingDetailPageTabs = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelectorAll("[data-admin-booking-page-tab]") : [];
    var adminBookingDetailPages = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelectorAll("[data-admin-booking-page]") : [];
    var adminBookingDetailName = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-name]") : null;
    var adminBookingDetailEmail = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-email]") : null;
    var adminBookingDetailOrderNumber = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-order-number]") : null;
    var adminBookingDetailTimestamp = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-timestamp]") : null;
    var adminBookingDetailStatus = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-status]") : null;
    var adminBookingDetailDuration = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-duration]") : null;
    var adminBookingDetailPlace = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-place]") : null;
    var adminBookingDetailReceive = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive]") : null;
    var adminBookingDetailReturn = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return]") : null;
    var adminBookingDetailReceivingMethod = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receiving-method]") : null;
    var adminBookingDetailReturningMethod = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-returning-method]") : null;
    var adminBookingDetailCourier = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-courier]") : null;
    var adminBookingDetailPaymentMethod = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-payment-method]") : null;
    var adminBookingDetailCustomerGcashName = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-customer-gcash-name]") : null;
    var adminBookingDetailCustomerGcashNumber = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-customer-gcash-number]") : null;
    var adminBookingDetailReceiptState = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receipt-state]") : null;
    var adminBookingDetailRefundProofState = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-refund-proof-state]") : null;
    var adminBookingDetailValidIdState = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-valid-id-state]") : null;
    var adminBookingDetailSelfieWithIdState = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-selfie-with-id-state]") : null;
    var adminBookingDetailCancelReason = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-cancel-reason]") : null;
    var adminBookingDetailItems = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-items]") : null;
    var adminBookingDetailReceiptWrap = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receipt-wrap]") : null;
    var adminBookingDetailReceiptLink = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receipt-link]") : null;
    var adminBookingDetailReceiptImage = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receipt-image]") : null;
    var adminBookingDetailReceiptEmpty = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receipt-empty]") : null;
    var adminBookingDetailReceiptMeta = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receipt-meta]") : null;
    var adminBookingDetailRefundWrap = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-refund-wrap]") : null;
    var adminBookingDetailRefundLink = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-refund-link]") : null;
    var adminBookingDetailRefundImage = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-refund-image]") : null;
    var adminBookingDetailRefundEmpty = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-refund-empty]") : null;
    var adminBookingDetailRefundMeta = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-refund-meta]") : null;
    var adminBookingDetailReceiveDeliveryWrap = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive-delivery-wrap]") : null;
    var adminBookingDetailReceiveDeliveryState = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive-delivery-state]") : null;
    var adminBookingDetailReceiveDeliveryLink = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive-delivery-link]") : null;
    var adminBookingDetailReceiveDeliveryImage = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive-delivery-image]") : null;
    var adminBookingDetailReceiveDeliveryEmpty = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive-delivery-empty]") : null;
    var adminBookingDetailReceiveDeliveryMeta = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-receive-delivery-meta]") : null;
    var adminBookingDetailReturnDeliveryWrap = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return-delivery-wrap]") : null;
    var adminBookingDetailReturnDeliveryState = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return-delivery-state]") : null;
    var adminBookingDetailReturnDeliveryLink = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return-delivery-link]") : null;
    var adminBookingDetailReturnDeliveryImage = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return-delivery-image]") : null;
    var adminBookingDetailReturnDeliveryEmpty = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return-delivery-empty]") : null;
    var adminBookingDetailReturnDeliveryMeta = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-return-delivery-meta]") : null;
    var adminBookingDetailValidIdWrap = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-valid-id-wrap]") : null;
    var adminBookingDetailValidIdLink = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-valid-id-link]") : null;
    var adminBookingDetailValidIdImage = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-valid-id-image]") : null;
    var adminBookingDetailValidIdEmpty = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-valid-id-empty]") : null;
    var adminBookingDetailValidIdMeta = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-valid-id-meta]") : null;
    var adminBookingDetailSelfieWithIdWrap = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-selfie-with-id-wrap]") : null;
    var adminBookingDetailSelfieWithIdLink = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-selfie-with-id-link]") : null;
    var adminBookingDetailSelfieWithIdImage = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-selfie-with-id-image]") : null;
    var adminBookingDetailSelfieWithIdEmpty = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-selfie-with-id-empty]") : null;
    var adminBookingDetailSelfieWithIdMeta = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-selfie-with-id-meta]") : null;
    var adminBookingDetailStatusNote = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-status-note]") : null;
    var adminBookingStatusForm = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-status-form]") : null;
    var adminBookingStatusOrderIdInput = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-status-order-id]") : null;
    var adminBookingNextStatusInput = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-next-status]") : null;
    var adminBookingCancelReasonHiddenInput = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-cancel-reason-input]") : null;
    var adminBookingRefundProofHiddenInput = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-refund-proof-input]") : null;
    var adminBookingStatusSubmitButtons = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelectorAll("[data-admin-booking-status-submit]") : [];
    var adminBookingReviewOpenButtons = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelectorAll("[data-admin-booking-review-open]") : [];
    var adminBookingDeliveryActionButtons = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelectorAll("[data-admin-booking-delivery-open]") : [];
    var adminBookingCancelOpenButton = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-cancel-open]") : null;
    var adminBookingDetailItemsTotal = adminBookingDetailBackdrop ? adminBookingDetailBackdrop.querySelector("[data-admin-booking-detail-items-total]") : null;
    var adminBookingCancelBackdrop = document.querySelector("[data-admin-booking-cancel-backdrop]");
    var adminBookingCancelCloseButtons = adminBookingCancelBackdrop ? adminBookingCancelBackdrop.querySelectorAll("[data-admin-booking-cancel-close]") : [];
    var adminBookingCancelReasonInput = adminBookingCancelBackdrop ? adminBookingCancelBackdrop.querySelector("[data-admin-booking-cancel-reason]") : null;
    var adminBookingCancelError = adminBookingCancelBackdrop ? adminBookingCancelBackdrop.querySelector("[data-admin-booking-cancel-error]") : null;
    var adminBookingCancelConfirmButton = adminBookingCancelBackdrop ? adminBookingCancelBackdrop.querySelector("[data-admin-booking-cancel-confirm]") : null;
    var adminBookingReviewBackdrop = document.querySelector("[data-admin-booking-review-backdrop]");
    var adminBookingReviewCloseButtons = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelectorAll("[data-admin-booking-review-close]") : [];
    var adminBookingReviewTitle = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-title]") : null;
    var adminBookingReviewCopy = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-copy]") : null;
    var adminBookingReviewCustomerGcashWrap = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-customer-gcash]") : null;
    var adminBookingReviewCustomerGcashName = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-customer-gcash-name]") : null;
    var adminBookingReviewCustomerGcashNumber = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-customer-gcash-number]") : null;
    var adminBookingReviewReasonInput = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-reason]") : null;
    var adminBookingReviewError = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-error]") : null;
    var adminBookingReviewConfirmButton = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-confirm]") : null;
    var adminBookingReviewProofWrap = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-proof-wrap]") : null;
    var adminBookingReviewProofFileInput = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-proof-file]") : null;
    var adminBookingReviewProofSelectButton = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-proof-select]") : null;
    var adminBookingReviewProofFilename = adminBookingReviewBackdrop ? adminBookingReviewBackdrop.querySelector("[data-admin-booking-review-proof-filename]") : null;
    var adminBookingDeliveryBackdrop = document.querySelector("[data-admin-booking-delivery-backdrop]");
    var adminBookingDeliveryCloseButtons = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelectorAll("[data-admin-booking-delivery-close]") : [];
    var adminBookingDeliveryTitle = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-title]") : null;
    var adminBookingDeliveryCopy = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-copy]") : null;
    var adminBookingDeliveryProofWrap = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-proof-wrap]") : null;
    var adminBookingDeliveryProofFileInput = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-proof-file]") : null;
    var adminBookingDeliveryProofSelectButton = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-proof-select]") : null;
    var adminBookingDeliveryProofFilename = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-proof-filename]") : null;
    var adminBookingDeliveryReferenceInput = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-reference]") : null;
    var adminBookingDeliveryNotesInput = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-notes]") : null;
    var adminBookingDeliveryError = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-error]") : null;
    var adminBookingDeliveryConfirmButton = adminBookingDeliveryBackdrop ? adminBookingDeliveryBackdrop.querySelector("[data-admin-booking-delivery-confirm]") : null;
    var adminBookingsSource = Array.isArray(window.__creatyAdminBookings) ? window.__creatyAdminBookings : [];
    var initialAdminBookingRecord = adminBookingsSource.length ? adminBookingsSource[0] : null;
    var initialAdminBookingId = initialAdminBookingRecord && initialAdminBookingRecord.id
        ? String(initialAdminBookingRecord.id)
        : "";

    if (initialAdminBookingId) {
        adminLiveLastOrderId = initialAdminBookingId.toUpperCase();
    }
    var shouldOpenAdminEquipmentArchiveModal = document.body.getAttribute("data-admin-open-equipment-archive-modal") === "true";
    var shouldOpenAdminEquipmentStatusModal = document.body.getAttribute("data-admin-open-equipment-status-modal") === "true";
    var adminActionModalBackdrop = document.querySelector("[data-admin-action-modal-backdrop]");
    var adminActionModalTitle = document.querySelector("[data-admin-action-modal-title]");
    var adminActionModalMessage = document.querySelector("[data-admin-action-modal-message]");
    var adminActionModalQuantityWrap = document.querySelector("[data-admin-action-modal-quantity-wrap]");
    var adminActionModalQuantityInput = document.querySelector("[data-admin-action-modal-quantity-input]");
    var adminActionModalConfirm = document.querySelector("[data-admin-action-modal-confirm]");
    var adminActionModalCancelButtons = document.querySelectorAll("[data-admin-action-modal-cancel], [data-admin-action-modal-close]");
    var productCards = document.querySelectorAll('.product-grid .product-card:not([data-admin-add-card="true"])');
    var adminAddCard = document.querySelector('[data-admin-add-card="true"]');
    var productEmpty = document.querySelector(".product-grid-empty");
    var catalogFilterForm = document.querySelector("[data-catalog-filter-form]");
    var catalogFilterCount = document.querySelector("[data-catalog-filter-count]");
    var catalogResultsCount = document.querySelector("[data-catalog-results-count]");
    var catalogViewResults = document.querySelector("[data-catalog-view-results]");
    var catalogResetButtons = document.querySelectorAll("[data-catalog-filter-reset]");
    var catalogPriceLimit = catalogFilterForm ? Number(catalogFilterForm.querySelector('[data-catalog-price="max"]').max) : Infinity;
    var catalogFilters = { skills: [], categories: [], min: 0, max: catalogPriceLimit };
    var adminRemoveButtons = document.querySelectorAll("[data-admin-remove-featured]");
    var adminEditButtons = document.querySelectorAll("[data-admin-edit-featured]");
    var adminEditBackdrop = document.querySelector("[data-admin-edit-backdrop]");
    var adminRestoreEndpoint = adminEditBackdrop ? (adminEditBackdrop.getAttribute("data-admin-restore-endpoint") || "") : "";
    var adminUndoToast = document.querySelector("[data-admin-undo-toast]");
    var adminUndoMessage = document.querySelector("[data-admin-undo-message]");
    var adminUndoAction = document.querySelector("[data-admin-undo-action]");
    var adminEditForm = document.querySelector("[data-admin-edit-form]");
    var adminEditClose = document.querySelector("[data-admin-edit-close]");
    var adminEditCancel = document.querySelector("[data-admin-edit-cancel]");
    var adminEditDuplicate = document.querySelector("[data-admin-edit-duplicate]");
    var adminEditBrowse = document.querySelector("[data-admin-edit-browse]");
    var adminEditRecrop = document.querySelector("[data-admin-edit-recrop]");
    var adminEditImageActions = document.querySelector("[data-admin-edit-image-actions]");
    var adminEditMainActions = document.querySelector("[data-admin-edit-main-actions]");
    var adminCropWorkspace = document.querySelector("[data-admin-crop-workspace]");
    var adminEditCropCancel = document.querySelector("[data-admin-edit-crop-cancel]");
    var adminEditCropSave = document.querySelector("[data-admin-edit-crop-save]");
    var adminEditFileInput = document.querySelector("[data-admin-edit-file]");
    var adminEditPreviewWrap = document.querySelector("[data-admin-edit-image-preview]");
    var adminEditPreviewImage = document.querySelector("[data-admin-edit-preview-img]");
    var adminEditBrand = document.querySelector("[data-admin-edit-brand]");
    var adminEditName = document.querySelector("[data-admin-edit-name]");
    var adminEditSpec1 = document.querySelector("[data-admin-edit-spec1]");
    var adminEditSpec2 = document.querySelector("[data-admin-edit-spec2]");
    var adminEditPrice = document.querySelector("[data-admin-edit-price]");
    var adminEditDiscount = document.querySelector("[data-admin-edit-discount]");
    var adminEditTagline = document.querySelector("[data-admin-edit-tagline]");
    var adminEditImagingSpecs = document.querySelector("[data-admin-edit-imaging-specs]");
    var adminEditVideoSpecs = document.querySelector("[data-admin-edit-video-specs]");
    var adminEditPhysicalSpecs = document.querySelector("[data-admin-edit-physical-specs]");
    var adminEditCaptureSlides = document.querySelector("[data-admin-edit-capture-slides]");
    var adminEditZoom = document.querySelector("[data-admin-edit-zoom]");
    var adminEventEditButtons = document.querySelectorAll("[data-admin-event-edit]");
    var adminEventEditBackdrop = document.querySelector("[data-admin-event-edit-backdrop]");
    var adminEventEditForm = document.querySelector("[data-admin-event-edit-form]");
    var adminEventEditClose = document.querySelector("[data-admin-event-edit-close]");
    var adminEventEditCancel = document.querySelector("[data-admin-event-edit-cancel]");
    var adminEventEditKey = document.querySelector("[data-admin-event-edit-key]");
    var adminEventEditName = document.querySelector("[data-admin-event-edit-name]");
    var adminEventEditPrice = document.querySelector("[data-admin-event-edit-price]");
    var adminEventEditDiscount = document.querySelector("[data-admin-event-edit-discount]");
    var adminEventEditCamera1 = document.querySelector("[data-admin-event-edit-camera-1]");
    var adminEventEditCamera2 = document.querySelector("[data-admin-event-edit-camera-2]");
    var adminEventEditBackupCamera1 = document.querySelector("[data-admin-event-edit-backup-camera-1]");
    var adminEventEditBackupCamera2 = document.querySelector("[data-admin-event-edit-backup-camera-2]");
    var adminServiceEditButtons = document.querySelectorAll("[data-admin-service-edit]");
    var adminServiceEditBackdrop = document.querySelector("[data-admin-service-edit-backdrop]");
    var adminServiceEditForm = document.querySelector("[data-admin-service-edit-form]");
    var adminServiceEditClose = document.querySelector("[data-admin-service-edit-close]");
    var adminServiceEditCancel = document.querySelector("[data-admin-service-edit-cancel]");
    var adminServiceEditKey = document.querySelector("[data-admin-service-edit-key]");
    var adminServiceEditName = document.querySelector("[data-admin-service-edit-name]");
    var adminServiceEditDescription = document.querySelector("[data-admin-service-edit-description]");
    var adminServiceEditPrice = document.querySelector("[data-admin-service-edit-price]");
    var adminServiceEditDiscount = document.querySelector("[data-admin-service-edit-discount]");
    var adminServiceEditDurationUnit = document.querySelector("[data-admin-service-edit-duration-unit]");
    var adminServiceEditDurationValue = document.querySelector("[data-admin-service-edit-duration-value]");
    var adminServiceEditCamera1 = document.querySelector("[data-admin-service-edit-camera-1]");
    var adminServiceEditCamera2 = document.querySelector("[data-admin-service-edit-camera-2]");
    var adminServiceEditBackupCamera1 = document.querySelector("[data-admin-service-edit-backup-camera-1]");
    var adminServiceEditBackupCamera2 = document.querySelector("[data-admin-service-edit-backup-camera-2]");
    var adminServiceEditTabButtons = document.querySelectorAll("[data-admin-service-edit-tab]");
    var adminServiceEditPanels = document.querySelectorAll("[data-admin-service-edit-panel]");
    var adminEventRemoveButtons = document.querySelectorAll("[data-admin-remove-event-package]");
    var adminEventSetThumbButtonInEdit = document.querySelector("[data-admin-event-set-thumbnails-edit]");
    var adminEventThumbsBackdrop = document.querySelector("[data-admin-event-thumbs-backdrop]");
    var adminEventThumbsForm = document.querySelector("[data-admin-event-thumbs-form]");
    var adminEventThumbsClose = document.querySelector("[data-admin-event-thumbs-close]");
    var adminEventThumbsCancel = document.querySelector("[data-admin-event-thumbs-cancel]");
    var adminEventThumbsKey = document.querySelector("[data-admin-event-thumbs-key]");
    var adminEventThumbsInput = document.querySelector("[data-admin-event-thumbs-input]");
    var adminEventThumbItems = document.querySelectorAll("[data-admin-event-thumb-item]");
    var adminEventThumbsPackageTitle = document.querySelector("[data-admin-event-thumbs-package-title]");
    var adminEventThumbsFolderEmpty = document.querySelector("[data-admin-event-thumbs-folder-empty]");
    var adminServiceSetThumbButtonInEdit = document.querySelector("[data-admin-service-set-thumbnails-edit]");
    var adminServiceThumbsBackdrop = document.querySelector("[data-admin-service-thumbs-backdrop]");
    var adminServiceThumbsForm = document.querySelector("[data-admin-service-thumbs-form]");
    var adminServiceThumbsClose = document.querySelector("[data-admin-service-thumbs-close]");
    var adminServiceThumbsCancel = document.querySelector("[data-admin-service-thumbs-cancel]");
    var adminServiceThumbsKey = document.querySelector("[data-admin-service-thumbs-key]");
    var adminServiceThumbsInput = document.querySelector("[data-admin-service-thumbs-input]");
    var adminServiceThumbItems = document.querySelectorAll("[data-admin-service-thumb-item]");
    var adminServiceThumbsPackageTitle = document.querySelector("[data-admin-service-thumbs-package-title]");
    var adminServiceThumbsFolderEmpty = document.querySelector("[data-admin-service-thumbs-folder-empty]");
    var adminEventArchiveEndpoint = adminEventEditBackdrop ? (adminEventEditBackdrop.getAttribute("data-admin-event-archive-endpoint") || "") : "";
    var adminEventRestoreEndpoint = adminEventEditBackdrop ? (adminEventEditBackdrop.getAttribute("data-admin-event-restore-endpoint") || "") : "";
    var adminEventCollectionConfig = document.querySelector("[data-admin-event-collection-config]");
    var adminEventCollectionCreateOpenButton = document.querySelector("[data-admin-event-collection-add-open]");
    var adminEventCollectionEditButtons = document.querySelectorAll("[data-admin-event-collection-edit]");
    var adminEventCollectionRemoveButtons = document.querySelectorAll("[data-admin-remove-event-collection]");
    var adminEventCollectionCards = document.querySelectorAll("[data-admin-event-collection-card]");
    var adminEventCollectionsShell = document.querySelector("[data-admin-event-collections-shell]");
    var adminEventCollectionsEmptyState = document.querySelector("[data-admin-event-collections-empty]");
    var adminEventCollectionCountSingular = adminEventCollectionsShell
        ? String(adminEventCollectionsShell.getAttribute("data-admin-event-collection-count-singular") || "event").trim()
        : "event";
    var adminEventCollectionCountPlural = adminEventCollectionsShell
        ? String(adminEventCollectionsShell.getAttribute("data-admin-event-collection-count-plural") || ((adminEventCollectionCountSingular || "event") + "s")).trim()
        : "events";
    var adminEventCollectionCreateBackdrop = document.querySelector("[data-admin-event-collection-create-backdrop]");
    var adminEventCollectionCreateForm = document.querySelector("[data-admin-event-collection-create-form]");
    var adminEventCollectionCreateClose = document.querySelector("[data-admin-event-collection-create-close]");
    var adminEventCollectionCreateCancel = document.querySelector("[data-admin-event-collection-create-cancel]");
    var adminEventCollectionCreateSave = document.querySelector("[data-admin-event-collection-create-save]");
    var adminEventCollectionCreateCategoryInput = document.querySelector("[data-admin-event-collection-create-category]");
    var adminEventCollectionCreateNameInput = document.querySelector("[data-admin-event-collection-create-name]");
    var adminEventCollectionCreateFeedback = document.querySelector("[data-admin-event-collection-create-feedback]");
    var adminEventCollectionEditBackdrop = document.querySelector("[data-admin-event-collection-edit-backdrop]");
    var adminEventCollectionEditForm = document.querySelector("[data-admin-event-collection-edit-form]");
    var adminEventCollectionEditTitle = document.querySelector("[data-admin-event-collection-edit-title]");
    var adminEventCollectionEditClose = document.querySelector("[data-admin-event-collection-edit-close]");
    var adminEventCollectionEditCancel = document.querySelector("[data-admin-event-collection-edit-cancel]");
    var adminEventCollectionEditSave = document.querySelector("[data-admin-event-collection-edit-save]");
    var adminEventCollectionEditGrid = document.querySelector("[data-admin-event-collection-edit-grid]");
    var adminEventCollectionEditEmpty = document.querySelector("[data-admin-event-collection-edit-empty]");
    var adminEventCollectionEditFeedback = document.querySelector("[data-admin-event-collection-edit-feedback]");
    var adminEventCollectionEditPackageKeyInput = document.querySelector("[data-admin-event-collection-edit-package-key]");
    var adminEventCollectionEditPackageFolderInput = document.querySelector("[data-admin-event-collection-edit-package-folder]");
    var adminEventCollectionEditCollectionFolderInput = document.querySelector("[data-admin-event-collection-edit-collection-folder]");
    var adminEventCollectionEditCategoryInput = document.querySelector("[data-admin-event-collection-edit-category]");
    var adminEventCollectionEditNameInput = document.querySelector("[data-admin-event-collection-edit-name]");
    var adminEventCollectionAddTrigger = document.querySelector("[data-admin-event-collection-add-trigger]");
    var adminEventCollectionAddInput = document.querySelector("[data-admin-event-collection-add-input]");
    var adminEventCollectionCreateEndpoint = adminEventCollectionConfig ? (adminEventCollectionConfig.getAttribute("data-admin-event-collection-create-endpoint") || "") : "";
    var adminEventCollectionArchiveEndpoint = adminEventCollectionConfig ? (adminEventCollectionConfig.getAttribute("data-admin-event-collection-archive-endpoint") || "") : "";
    var adminEventCollectionRestoreEndpoint = adminEventCollectionConfig ? (adminEventCollectionConfig.getAttribute("data-admin-event-collection-restore-endpoint") || "") : "";
    var adminEventCollectionUpdateEndpoint = adminEventCollectionConfig ? (adminEventCollectionConfig.getAttribute("data-admin-event-collection-update-endpoint") || "") : "";
    var adminEventCollectionAssetBase = adminEventCollectionConfig ? (adminEventCollectionConfig.getAttribute("data-admin-event-collection-asset-base") || "") : "";
    var adminEventCollectionDefaultPackageKey = adminEventCollectionConfig ? (adminEventCollectionConfig.getAttribute("data-admin-event-collection-package-key") || "") : "";
    var adminEventCollectionDefaultPackageFolder = adminEventCollectionConfig ? (adminEventCollectionConfig.getAttribute("data-admin-event-collection-package-folder") || "") : "";
    var adminEventCollectionAllowVideo = adminEventCollectionConfig
        ? /^(1|true|yes)$/i.test(String(adminEventCollectionConfig.getAttribute("data-admin-event-collection-allow-video") || "0").trim())
        : false;
    var adminHowGrid = document.querySelector("[data-admin-how-grid]");
    var adminHowEditBackdrop = document.querySelector("[data-admin-how-edit-backdrop]");
    var adminHowForm = document.querySelector("[data-admin-how-form]");
    var adminHowClose = document.querySelector("[data-admin-how-close]");
    var adminHowCancel = document.querySelector("[data-admin-how-cancel]");
    var adminHowBrowse = document.querySelector("[data-admin-how-browse]");
    var adminHowRecrop = document.querySelector("[data-admin-how-recrop]");
    var adminHowImageActions = document.querySelector("[data-admin-how-image-actions]");
    var adminHowMainActions = document.querySelector("[data-admin-how-main-actions]");
    var adminHowCropWorkspace = document.querySelector("[data-admin-how-crop-workspace]");
    var adminHowCropCancel = document.querySelector("[data-admin-how-crop-cancel]");
    var adminHowCropSave = document.querySelector("[data-admin-how-crop-save]");
    var adminHowFileInput = document.querySelector("[data-admin-how-file]");
    var adminHowPreviewWrap = document.querySelector("[data-admin-how-preview-wrap]");
    var adminHowPreviewImage = document.querySelector("[data-admin-how-preview-img]");
    var adminHowZoom = document.querySelector("[data-admin-how-zoom]");
    var adminHowSlotNote = document.querySelector("[data-admin-how-slot-note]");
    var adminHowRestoreEndpoint = adminHowGrid ? (adminHowGrid.getAttribute("data-admin-how-restore-endpoint") || "") : "";
    var adminHowImageBase = adminHowGrid ? (adminHowGrid.getAttribute("data-admin-how-image-base") || "assets/how_it_works/") : "assets/how_it_works/";
    var activeAdminEditCard = null;
    var activeAdminEventPackageCard = null;
    var activeAdminServicePackageCard = null;
    var activeAdminEventThumbsCard = null;
    var activeAdminEventThumbFolder = "";
    var adminEventThumbSelection = [];
    var activeAdminServiceThumbsCard = null;
    var activeAdminServiceThumbFolder = "";
    var adminServiceThumbSelection = [];
    var activeAdminHowSlot = "";
    var adminCropState = {
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        isCropping: false,
        isDragging: false,
        dragPointerId: null,
        dragStartClientX: 0,
        dragStartClientY: 0,
        dragStartOffsetX: 0,
        dragStartOffsetY: 0,
        sourceImage: "",
        previewBeforeCrop: ""
    };
    var adminCoverAspect = {
        width: 5,
        height: 4
    };
    var adminHowCropState = {
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        isCropping: false,
        isDragging: false,
        dragPointerId: null,
        dragStartClientX: 0,
        dragStartClientY: 0,
        dragStartOffsetX: 0,
        dragStartOffsetY: 0,
        sourceImage: "",
        previewBeforeCrop: ""
    };
    var adminHowAspect = {
        width: 3,
        height: 2
    };
    var activeAdminPromoSlot = "";
    var adminPromoCropState = {
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        isCropping: false,
        isDragging: false,
        dragPointerId: null,
        dragStartClientX: 0,
        dragStartClientY: 0,
        dragStartOffsetX: 0,
        dragStartOffsetY: 0,
        sourceImage: "",
        previewBeforeCrop: ""
    };
    var adminPromoAspect = {
        width: 3,
        height: 1
    };
    var adminUndoState = {
        timerId: null,
        pending: null
    };
    var adminActionModalState = {
        onConfirm: null,
        onClose: null,
        quantityRequired: false
    };
    var adminEventCollectionEditState = {
        card: null,
        packageKey: "",
        packageFolder: "",
        collectionFolder: "",
        collectionName: "",
        categoryLabel: "",
        images: [],
        isSaving: false,
        tempCounter: 0
    };
    var adminEventCollectionCreateState = {
        isSaving: false
    };
    var activeAdminBookingCancelReason = "";
    var activeAdminBookingReviewMode = "";
    var activeAdminBookingDeliveryMode = "";
    var activeAdminBookingDeliveryOrderId = "";
    var isSubmittingAdminBookingDelivery = false;
    var activeAdminBookingDetailPage = "items";
    var detailGalleries = document.querySelectorAll("[data-gallery]");
    var packageSlideshows = document.querySelectorAll("[data-package-slideshow]");
    var packageSlideshowControllers = [];
    var activeFilters = {
        brand: "all",
        month: "all",
        day: "all",
        year: "all"
    };
    var monthDefinitions = [
        { value: "january", label: "January" },
        { value: "february", label: "February" },
        { value: "march", label: "March" },
        { value: "april", label: "April" },
        { value: "may", label: "May" },
        { value: "june", label: "June" },
        { value: "july", label: "July" },
        { value: "august", label: "August" },
        { value: "september", label: "September" },
        { value: "october", label: "October" },
        { value: "november", label: "November" },
        { value: "december", label: "December" }
    ];
    var monthValueToIndex = monthDefinitions.reduce(function (result, month, index) {
        result[month.value] = index;
        return result;
    }, {});
    var dateFilterPanel = filterNav ? filterNav.querySelector("#date-filter-panel") : null;
    var dateTabButtons = dateFilterPanel ? dateFilterPanel.querySelectorAll("[data-date-tab]") : [];
    var dateViews = dateFilterPanel ? dateFilterPanel.querySelectorAll("[data-date-view]") : [];
    var monthOptionsWrap = dateFilterPanel ? dateFilterPanel.querySelector("[data-date-month-options]") : null;
    var yearOptionsWrap = dateFilterPanel ? dateFilterPanel.querySelector("[data-date-year-options]") : null;
    var calendarGrid = dateFilterPanel ? dateFilterPanel.querySelector("[data-date-calendar-grid]") : null;
    var calendarTitle = dateFilterPanel ? dateFilterPanel.querySelector("[data-calendar-title]") : null;
    var availableDateKeys = new Set();
    var availableYears = [];
    var now = new Date();
    var calendarViewMonthIndex = now.getMonth();
    var calendarViewYear = now.getFullYear();
    var manageBrandsOptionValue = "__manage_brands__";
    var brandValueToLabel = {};

    function formatBrandLabelFromValue(value) {
        var normalized = String(value || "")
            .replace(/[_-]+/g, " ")
            .replace(/\s+/g, " ")
            .trim();

        if (!normalized) {
            return "";
        }

        return normalized.replace(/\b\w/g, function (character) {
            return character.toUpperCase();
        });
    }

    function registerBrandValueLabel(value, label) {
        var normalizedValue = String(value || "").toLowerCase().trim();
        if (!normalizedValue || normalizedValue === "all" || normalizedValue === manageBrandsOptionValue) {
            return;
        }

        var normalizedLabel = String(label || "").replace(/\s+/g, " ").trim();

        if (!normalizedLabel || normalizedLabel === normalizedLabel.toUpperCase()) {
            normalizedLabel = formatBrandLabelFromValue(normalizedValue);
        }

        if (normalizedLabel && !Object.prototype.hasOwnProperty.call(brandValueToLabel, normalizedValue)) {
            brandValueToLabel[normalizedValue] = normalizedLabel;
        }
    }

    function resolveCreatyAppRootPath() {
        var pathname = String(window.location.pathname || "/");
        var segments = pathname.split("/").filter(function (segment) {
            return segment !== "";
        });
        var routeAnchors = {
            admin: true,
            archive: true,
            "customer-account-settings": true,
            "customer-cart": true,
            "customer-event": true,
            "customer-events": true,
            "customer-login": true,
            "customer-logout": true,
            "customer-products": true,
            "customer-service": true,
            "customer-services": true,
            "customer-signup": true,
            "customer-verify-email": true,
            staff: true
        };

        for (var index = 0; index < segments.length; index += 1) {
            var segmentToken = String(segments[index] || "").toLowerCase().trim();

            if (!routeAnchors[segmentToken]) {
                continue;
            }

            var rootSegments = segments.slice(0, index);
            return "/" + (rootSegments.length ? rootSegments.join("/") + "/" : "");
        }

        if (!segments.length) {
            return "/";
        }

        var lastSegment = String(segments[segments.length - 1] || "").toLowerCase().trim();

        if (lastSegment.indexOf(".php") !== -1) {
            var parentSegments = segments.slice(0, segments.length - 1);
            return "/" + (parentSegments.length ? parentSegments.join("/") + "/" : "");
        }

        if (pathname.slice(-1) === "/") {
            return "/" + segments.join("/") + "/";
        }

        var fallbackSegments = segments.slice(0, segments.length - 1);
        return "/" + (fallbackSegments.length ? fallbackSegments.join("/") + "/" : "");
    }

    function isAdminUniversalSearchContext() {
        var pathname = String(window.location.pathname || "").toLowerCase();

        if (pathname.indexOf("/admin/") !== -1) {
            return true;
        }

        if (document.querySelector("[data-admin-dashboard-nav]")) {
            return true;
        }

        return Boolean(document.querySelector(".topbar.topbar-admin"));
    }

    function initializeUniversalTopbarSearch() {
        var topbarSearchForms = document.querySelectorAll("form.topbar-search");

        if (!topbarSearchForms.length) {
            return;
        }

        var appRootPath = resolveCreatyAppRootPath();
        var isAdminContext = isAdminUniversalSearchContext();
        var defaultTargetPath = appRootPath + (isAdminContext ? "admin/products/" : "customer-products/");
        var suggestionsEndpointPath = appRootPath + "search_suggestions.php";
        var fallbackThumbnailPath = appRootPath + "assets/images/main_logo.png";
        var minimumSuggestionQueryLength = 2;
        var suggestionFetchDebounceMs = 240;
        var maxCachedSuggestionQueries = 40;
        var suggestionsCache = new Map();

        function buildSuggestionsCacheKey(queryValue) {
            return (isAdminContext ? "admin" : "customer") + "|" + String(queryValue || "").toLowerCase().trim();
        }

        function readCachedSuggestions(queryValue) {
            var cacheKey = buildSuggestionsCacheKey(queryValue);

            if (!suggestionsCache.has(cacheKey)) {
                return null;
            }

            var cachedSuggestions = suggestionsCache.get(cacheKey);

            // Refresh key recency so recent searches stay cached longer.
            suggestionsCache.delete(cacheKey);
            suggestionsCache.set(cacheKey, cachedSuggestions);

            if (!Array.isArray(cachedSuggestions)) {
                return [];
            }

            return cachedSuggestions;
        }

        function writeCachedSuggestions(queryValue, suggestionItems) {
            var cacheKey = buildSuggestionsCacheKey(queryValue);
            var normalizedSuggestions = Array.isArray(suggestionItems) ? suggestionItems : [];

            suggestionsCache.set(cacheKey, normalizedSuggestions);

            while (suggestionsCache.size > maxCachedSuggestionQueries) {
                var oldestEntryKey = suggestionsCache.keys().next();

                if (!oldestEntryKey || oldestEntryKey.done) {
                    break;
                }

                suggestionsCache.delete(oldestEntryKey.value);
            }
        }

        function buildDefaultSearchUrl(queryValue) {
            var normalizedQuery = String(queryValue || "").trim();

            if (normalizedQuery === "") {
                return defaultTargetPath;
            }

            return defaultTargetPath + "?q=" + encodeURIComponent(normalizedQuery);
        }

        function resolveSuggestionTargetUrl(targetPath, queryValue) {
            var normalizedTargetPath = String(targetPath || "").trim();

            if (normalizedTargetPath === "") {
                return buildDefaultSearchUrl(queryValue);
            }

            try {
                return new URL(normalizedTargetPath, window.location.origin + appRootPath).toString();
            } catch (error) {
                return buildDefaultSearchUrl(queryValue);
            }
        }

        function resolveSuggestionThumbnailUrl(thumbnailPath) {
            var normalizedThumbnailPath = String(thumbnailPath || "").trim();

            if (normalizedThumbnailPath === "") {
                normalizedThumbnailPath = fallbackThumbnailPath;
            }

            try {
                return new URL(normalizedThumbnailPath, window.location.origin + appRootPath).toString();
            } catch (error) {
                return new URL(fallbackThumbnailPath, window.location.origin + appRootPath).toString();
            }
        }

        function clampDiscountPercent(value) {
            var parsed = parseInt(value, 10);

            if (!isFinite(parsed)) {
                return 0;
            }

            if (parsed < 0) {
                return 0;
            }

            if (parsed > 95) {
                return 95;
            }

            return parsed;
        }

        function setOptionActiveState(options, activeIndex) {
            Array.prototype.forEach.call(options, function (option, index) {
                var isActive = index === activeIndex;
                option.classList.toggle("is-active", isActive);
                option.setAttribute("aria-selected", isActive ? "true" : "false");

                if (isActive && typeof option.scrollIntoView === "function") {
                    option.scrollIntoView({ block: "nearest" });
                }
            });
        }

        topbarSearchForms.forEach(function (searchForm) {
            var searchInput = searchForm.querySelector('input[name="q"]');

            if (!searchInput) {
                return;
            }

            var suggestionsPanel = document.createElement("div");
            suggestionsPanel.className = "topbar-search-suggestions";
            suggestionsPanel.hidden = true;
            suggestionsPanel.setAttribute("role", "listbox");
            searchForm.appendChild(suggestionsPanel);

            searchInput.setAttribute("autocomplete", "off");
            searchInput.setAttribute("aria-autocomplete", "list");
            searchInput.setAttribute("aria-haspopup", "listbox");
            searchInput.setAttribute("aria-expanded", "false");

            var activeSuggestionIndex = -1;
            var suggestions = [];
            var debounceTimer = 0;
            var requestSerial = 0;
            var requestController = null;
            var skipBlurClose = false;
            var loadingIndicatorTimer = 0;

            function showSuggestionsPanel() {
                if (!suggestionsPanel.childNodes.length) {
                    return;
                }

                suggestionsPanel.hidden = false;
                searchInput.setAttribute("aria-expanded", "true");
            }

            function hideSuggestionsPanel() {
                suggestionsPanel.hidden = true;
                searchInput.setAttribute("aria-expanded", "false");
                activeSuggestionIndex = -1;
                setOptionActiveState(suggestionsPanel.querySelectorAll(".topbar-search-suggestion"), -1);
            }

            function renderSuggestionsStatusRow(className, message) {
                suggestionsPanel.innerHTML = "";

                var statusRow = document.createElement("div");
                statusRow.className = className;
                statusRow.textContent = String(message || "").trim();
                suggestionsPanel.appendChild(statusRow);
                showSuggestionsPanel();
            }

            function updateActiveSuggestion(nextIndex) {
                var optionNodes = suggestionsPanel.querySelectorAll(".topbar-search-suggestion");

                if (!optionNodes.length) {
                    activeSuggestionIndex = -1;
                    return;
                }

                if (nextIndex < 0 || nextIndex >= optionNodes.length) {
                    activeSuggestionIndex = -1;
                    setOptionActiveState(optionNodes, -1);
                    return;
                }

                activeSuggestionIndex = nextIndex;
                setOptionActiveState(optionNodes, activeSuggestionIndex);
            }

            function renderSuggestions(suggestionItems) {
                suggestions = Array.isArray(suggestionItems) ? suggestionItems : [];
                suggestionsPanel.innerHTML = "";
                activeSuggestionIndex = -1;

                if (!suggestions.length) {
                    renderSuggestionsStatusRow("topbar-search-empty", "No matches yet. Press Enter to search.");
                    return;
                }

                suggestions.forEach(function (suggestion, index) {
                    var targetUrl = resolveSuggestionTargetUrl(suggestion.targetPath, searchInput.value);
                    var titleText = String(suggestion.title || "Search result").trim();
                    var descriptionText = String(suggestion.description || "").trim();
                    var typeLabel = String(suggestion.typeLabel || "Result").trim();
                    var priceLabel = String(suggestion.priceLabel || "").trim();
                    var discountedPriceLabel = String(suggestion.discountedPriceLabel || "").trim();
                    var discountPercent = clampDiscountPercent(suggestion.discountPercent);

                    var suggestionNode = document.createElement("a");
                    suggestionNode.className = "topbar-search-suggestion";
                    suggestionNode.href = targetUrl;
                    suggestionNode.setAttribute("role", "option");
                    suggestionNode.setAttribute("aria-selected", "false");
                    suggestionNode.setAttribute("data-suggestion-index", String(index));

                    suggestionNode.addEventListener("mouseenter", function () {
                        updateActiveSuggestion(index);
                    });

                    suggestionNode.addEventListener("mousedown", function () {
                        skipBlurClose = true;

                        window.setTimeout(function () {
                            skipBlurClose = false;
                        }, 180);
                    });

                    var thumbNode = document.createElement("span");
                    thumbNode.className = "topbar-search-suggestion-thumb";

                    var thumbImage = document.createElement("img");
                    thumbImage.src = resolveSuggestionThumbnailUrl(suggestion.thumbnailPath);
                    thumbImage.alt = "";
                    thumbImage.loading = "lazy";
                    thumbImage.decoding = "async";
                    thumbImage.width = 52;
                    thumbImage.height = 52;
                    thumbNode.appendChild(thumbImage);

                    var bodyNode = document.createElement("span");
                    bodyNode.className = "topbar-search-suggestion-body";

                    var headingRow = document.createElement("span");
                    headingRow.className = "topbar-search-suggestion-heading";

                    var titleNode = document.createElement("span");
                    titleNode.className = "topbar-search-suggestion-title";
                    titleNode.textContent = titleText;

                    var typeNode = document.createElement("span");
                    typeNode.className = "topbar-search-suggestion-type";
                    typeNode.textContent = typeLabel;

                    headingRow.appendChild(titleNode);
                    headingRow.appendChild(typeNode);
                    bodyNode.appendChild(headingRow);

                    if (descriptionText !== "") {
                        var descriptionNode = document.createElement("span");
                        descriptionNode.className = "topbar-search-suggestion-description";
                        descriptionNode.textContent = descriptionText;
                        bodyNode.appendChild(descriptionNode);
                    }

                    var priceRow = document.createElement("span");
                    priceRow.className = "topbar-search-suggestion-price";

                    if (discountPercent > 0 && discountedPriceLabel !== "") {
                        if (priceLabel !== "") {
                            var originalPriceNode = document.createElement("span");
                            originalPriceNode.className = "topbar-search-price-original";
                            originalPriceNode.textContent = priceLabel;
                            priceRow.appendChild(originalPriceNode);
                        }

                        var discountedPriceNode = document.createElement("span");
                        discountedPriceNode.className = "topbar-search-price-discounted";
                        discountedPriceNode.textContent = discountedPriceLabel;
                        priceRow.appendChild(discountedPriceNode);

                        var discountNode = document.createElement("span");
                        discountNode.className = "topbar-search-price-badge";
                        discountNode.textContent = discountPercent + "% OFF";
                        priceRow.appendChild(discountNode);
                    } else if (priceLabel !== "") {
                        var currentPriceNode = document.createElement("span");
                        currentPriceNode.className = "topbar-search-price-discounted";
                        currentPriceNode.textContent = priceLabel;
                        priceRow.appendChild(currentPriceNode);
                    }

                    if (priceRow.childNodes.length) {
                        bodyNode.appendChild(priceRow);
                    }

                    suggestionNode.appendChild(thumbNode);
                    suggestionNode.appendChild(bodyNode);
                    suggestionsPanel.appendChild(suggestionNode);
                });

                showSuggestionsPanel();
                updateActiveSuggestion(-1);
            }

            function requestSuggestions(rawQuery) {
                var queryValue = String(rawQuery || "").trim();

                if (queryValue === "") {
                    suggestions = [];
                    hideSuggestionsPanel();
                    return;
                }

                if (queryValue.length < minimumSuggestionQueryLength) {
                    suggestions = [];
                    renderSuggestionsStatusRow(
                        "topbar-search-empty",
                        "Type at least " + minimumSuggestionQueryLength + " characters for suggestions."
                    );
                    return;
                }

                var cachedSuggestions = readCachedSuggestions(queryValue);

                if (cachedSuggestions) {
                    renderSuggestions(cachedSuggestions);
                    return;
                }

                if (requestController && typeof requestController.abort === "function") {
                    requestController.abort();
                }

                if (loadingIndicatorTimer) {
                    window.clearTimeout(loadingIndicatorTimer);
                }

                loadingIndicatorTimer = window.setTimeout(function () {
                    renderSuggestionsStatusRow("topbar-search-loading", "Searching...");
                }, 120);

                requestSerial += 1;
                var currentRequestSerial = requestSerial;
                requestController = typeof AbortController !== "undefined" ? new AbortController() : null;

                var endpointUrl;

                try {
                    endpointUrl = new URL(suggestionsEndpointPath, window.location.origin);
                } catch (error) {
                    if (loadingIndicatorTimer) {
                        window.clearTimeout(loadingIndicatorTimer);
                        loadingIndicatorTimer = 0;
                    }

                    renderSuggestionsStatusRow("topbar-search-empty", "No matches yet. Press Enter to search.");
                    return;
                }

                endpointUrl.searchParams.set("q", queryValue);
                endpointUrl.searchParams.set("context", isAdminContext ? "admin" : "customer");
                endpointUrl.searchParams.set("limit", "8");

                var requestOptions = {
                    credentials: "same-origin"
                };

                if (requestController && requestController.signal) {
                    requestOptions.signal = requestController.signal;
                }

                fetch(endpointUrl.toString(), requestOptions)
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error("Search request failed");
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        if (currentRequestSerial !== requestSerial) {
                            return;
                        }

                        if (loadingIndicatorTimer) {
                            window.clearTimeout(loadingIndicatorTimer);
                            loadingIndicatorTimer = 0;
                        }

                        var payloadSuggestions = Array.isArray(payload && payload.suggestions)
                            ? payload.suggestions
                            : [];

                        writeCachedSuggestions(queryValue, payloadSuggestions);

                        renderSuggestions(payloadSuggestions);
                    })
                    .catch(function (error) {
                        if (error && error.name === "AbortError") {
                            if (loadingIndicatorTimer) {
                                window.clearTimeout(loadingIndicatorTimer);
                                loadingIndicatorTimer = 0;
                            }

                            return;
                        }

                        if (currentRequestSerial !== requestSerial) {
                            return;
                        }

                        if (loadingIndicatorTimer) {
                            window.clearTimeout(loadingIndicatorTimer);
                            loadingIndicatorTimer = 0;
                        }

                        renderSuggestionsStatusRow("topbar-search-empty", "No matches yet. Press Enter to search.");
                    });
            }

            function scheduleSuggestions(rawQuery) {
                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                }

                debounceTimer = window.setTimeout(function () {
                    requestSuggestions(rawQuery);
                }, suggestionFetchDebounceMs);
            }

            searchInput.addEventListener("input", function () {
                scheduleSuggestions(searchInput.value);
            });

            searchInput.addEventListener("focus", function () {
                if (!suggestionsPanel.hidden && suggestionsPanel.childNodes.length) {
                    showSuggestionsPanel();
                    return;
                }

                if (String(searchInput.value || "").trim() !== "") {
                    scheduleSuggestions(searchInput.value);
                }
            });

            searchInput.addEventListener("blur", function () {
                window.setTimeout(function () {
                    if (skipBlurClose) {
                        return;
                    }

                    hideSuggestionsPanel();
                }, 120);
            });

            searchInput.addEventListener("keydown", function (event) {
                var hasVisibleSuggestions = !suggestionsPanel.hidden && suggestions.length > 0;

                if (event.key === "ArrowDown") {
                    if (!hasVisibleSuggestions) {
                        return;
                    }

                    event.preventDefault();

                    var nextDownIndex = activeSuggestionIndex + 1;
                    if (nextDownIndex >= suggestions.length) {
                        nextDownIndex = 0;
                    }

                    updateActiveSuggestion(nextDownIndex);
                    return;
                }

                if (event.key === "ArrowUp") {
                    if (!hasVisibleSuggestions) {
                        return;
                    }

                    event.preventDefault();

                    var nextUpIndex = activeSuggestionIndex - 1;
                    if (nextUpIndex < 0) {
                        nextUpIndex = suggestions.length - 1;
                    }

                    updateActiveSuggestion(nextUpIndex);
                    return;
                }

                if (event.key === "Escape") {
                    if (!suggestionsPanel.hidden) {
                        event.preventDefault();
                        hideSuggestionsPanel();
                    }

                    return;
                }

                if (event.key === "Enter") {
                    var queryValue = String(searchInput.value || "").trim();

                    if (queryValue === "") {
                        return;
                    }

                    if (hasVisibleSuggestions && activeSuggestionIndex >= 0 && suggestions[activeSuggestionIndex]) {
                        event.preventDefault();
                        window.location.href = resolveSuggestionTargetUrl(
                            suggestions[activeSuggestionIndex].targetPath,
                            queryValue
                        );
                    }
                }
            });

            searchForm.addEventListener("submit", function (event) {
                var queryValue = String(searchInput.value || "").trim();

                if (queryValue === "") {
                    hideSuggestionsPanel();
                    return;
                }

                event.preventDefault();

                if (!suggestionsPanel.hidden && activeSuggestionIndex >= 0 && suggestions[activeSuggestionIndex]) {
                    window.location.href = resolveSuggestionTargetUrl(suggestions[activeSuggestionIndex].targetPath, queryValue);
                    return;
                }

                window.location.href = buildDefaultSearchUrl(queryValue);
            });

            searchForm.addEventListener("mousedown", function () {
                skipBlurClose = true;

                window.setTimeout(function () {
                    skipBlurClose = false;
                }, 180);
            });

            document.addEventListener("click", function (event) {
                if (searchForm.contains(event.target)) {
                    return;
                }

                hideSuggestionsPanel();
            });
        });
    }

    if (adminEditBrand && adminEditBrand.options) {
        Array.prototype.forEach.call(adminEditBrand.options, function (option) {
            if (!option) {
                return;
            }

            registerBrandValueLabel(option.value, option.textContent);
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-filter-group="brand"][data-filter-value]'), function (button) {
        if (!button) {
            return;
        }

        registerBrandValueLabel(button.getAttribute("data-filter-value"), button.textContent);
    });

    if (!Object.keys(brandValueToLabel).length) {
        registerBrandValueLabel("canon", "Canon");
        registerBrandValueLabel("fuji", "Fuji");
        registerBrandValueLabel("nikon", "Nikon");
        registerBrandValueLabel("sony", "Sony");
    }

    var defaultBrandValue = Object.keys(brandValueToLabel)[0] || "canon";
    var adminProducts = (typeof window.__creatyAdminProducts === "object" && window.__creatyAdminProducts)
        ? window.__creatyAdminProducts
        : {};

    revealItems.forEach(function (item, index) {
        window.setTimeout(function () {
            item.classList.add("is-visible");
        }, 120 * (index + 1));
    });

    if (document.body.classList.contains("login-page") && authSwitchLinks.length) {
        var switchStorageKey = "creaty-auth-switch";

        try {
            if (window.sessionStorage.getItem(switchStorageKey) === "1") {
                document.body.classList.add("is-auth-switched");
                window.sessionStorage.removeItem(switchStorageKey);
                window.setTimeout(function () {
                    document.body.classList.remove("is-auth-switched");
                }, 600);
            }
        } catch (error) {
            // Ignore storage errors in private browsing contexts.
        }

        authSwitchLinks.forEach(function (link) {
            link.addEventListener("click", function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                var targetHref = link.getAttribute("href");

                if (!targetHref) {
                    return;
                }

                event.preventDefault();

                try {
                    window.sessionStorage.setItem(switchStorageKey, "1");
                } catch (error) {
                    // Ignore storage errors in private browsing contexts.
                }

                document.body.classList.add("is-auth-switching");

                window.setTimeout(function () {
                    window.location.href = targetHref;
                }, 180);
            });
        });
    }

    toggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var targetId = button.getAttribute("data-target");
            var passwordInput = document.getElementById(targetId);
            var icon = button.querySelector("img");
            var visibleIcon = button.getAttribute("data-icon-on");
            var hiddenIcon = button.getAttribute("data-icon-off");

            if (!passwordInput || !icon) {
                return;
            }

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.src = visibleIcon;
                button.setAttribute("aria-label", "Hide password");
                return;
            }

            passwordInput.type = "password";
            icon.src = hiddenIcon;
            button.setAttribute("aria-label", "Show password");
        });
    });

    function initializeCustomerMessageModal() {
        if (!messageUsOpenButtons.length || !customerMessageModal) {
            return;
        }

        var isSubmittingMessage = false;

        function setMessageFeedback(message, type) {
            if (!customerMessageFeedback) {
                return;
            }

            var normalizedMessage = String(message || "").trim();
            var normalizedType = type === "success" ? "is-success" : "is-error";

            customerMessageFeedback.hidden = normalizedMessage === "";
            customerMessageFeedback.textContent = normalizedMessage;
            customerMessageFeedback.classList.remove("is-success", "is-error");

            if (normalizedMessage !== "") {
                customerMessageFeedback.classList.add(normalizedType);
            }
        }

        function closeCustomerMessageModal() {
            customerMessageModal.hidden = true;
            document.body.classList.remove("admin-modal-open");

            if (!isSubmittingMessage) {
                setMessageFeedback("", "error");
            }
        }

        function openCustomerMessageModal() {
            customerMessageModal.hidden = false;
            document.body.classList.add("admin-modal-open");
            setMessageFeedback("", "error");

            var firstInput = customerMessageModal.querySelector("#customer-message-subject");
            if (firstInput) {
                window.setTimeout(function () {
                    firstInput.focus();
                }, 60);
            }
        }

        messageUsOpenButtons.forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();
                openCustomerMessageModal();
            });
        });

        customerMessageModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                if (isSubmittingMessage) {
                    return;
                }

                closeCustomerMessageModal();
            });
        });

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape" || customerMessageModal.hidden || isSubmittingMessage) {
                return;
            }

            closeCustomerMessageModal();
        });

        if (customerMessageAttachmentInput) {
            customerMessageAttachmentInput.addEventListener("change", function () {
                var fileCount = customerMessageAttachmentInput.files ? customerMessageAttachmentInput.files.length : 0;

                if (fileCount <= 5) {
                    return;
                }

                customerMessageAttachmentInput.value = "";
                setMessageFeedback("You can upload up to 5 images only.", "error");
            });
        }

        if (!customerMessageForm) {
            return;
        }

        customerMessageForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (isSubmittingMessage) {
                return;
            }

            var endpoint = customerMessageForm.getAttribute("action") || "";
            if (!endpoint) {
                setMessageFeedback("Message endpoint is unavailable.", "error");
                return;
            }

            var subjectInput = customerMessageForm.querySelector("#customer-message-subject");
            var messageInput = customerMessageForm.querySelector("#customer-message-body");
            var subjectValue = subjectInput ? String(subjectInput.value || "").trim() : "";
            var messageValue = messageInput ? String(messageInput.value || "").trim() : "";
            var attachmentCount = customerMessageAttachmentInput && customerMessageAttachmentInput.files
                ? customerMessageAttachmentInput.files.length
                : 0;

            if (!subjectValue || !messageValue) {
                setMessageFeedback("Subject and message are required.", "error");
                return;
            }

            if (attachmentCount > 5) {
                setMessageFeedback("You can upload up to 5 images only.", "error");
                return;
            }

            setMessageFeedback("", "error");
            isSubmittingMessage = true;

            var previousButtonLabel = customerMessageSubmitButton ? customerMessageSubmitButton.textContent : "Send Message";

            if (customerMessageSubmitButton) {
                customerMessageSubmitButton.disabled = true;
                customerMessageSubmitButton.textContent = "Sending...";
            }

            var payload = new FormData(customerMessageForm);

            fetch(endpoint, {
                method: "POST",
                body: payload,
                credentials: "same-origin"
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return {
                            ok: false,
                            message: "Unexpected server response."
                        };
                    }).then(function (body) {
                        return {
                            ok: response.ok,
                            body: body
                        };
                    });
                })
                .then(function (result) {
                    var responseMessage = result.body && result.body.message
                        ? String(result.body.message)
                        : "Unable to send your message right now.";

                    if (!result.ok || !result.body || !result.body.ok) {
                        throw new Error(responseMessage);
                    }

                    customerMessageForm.reset();
                    setMessageFeedback(responseMessage, "success");

                    window.setTimeout(function () {
                        isSubmittingMessage = false;

                        if (customerMessageSubmitButton) {
                            customerMessageSubmitButton.disabled = false;
                            customerMessageSubmitButton.textContent = previousButtonLabel;
                        }

                        closeCustomerMessageModal();
                    }, 700);
                })
                .catch(function (error) {
                    setMessageFeedback(error.message || "Unable to send your message right now.", "error");
                })
                .finally(function () {
                    if (isSubmittingMessage) {
                        isSubmittingMessage = false;

                        if (customerMessageSubmitButton) {
                            customerMessageSubmitButton.disabled = false;
                            customerMessageSubmitButton.textContent = previousButtonLabel;
                        }
                    }
                });
        });
    }

    function initializeAdminNotificationsPage() {
        if (!document.body.classList.contains("admin-notifications-page") || !adminNotificationModal || !adminNotificationItems.length) {
            return;
        }

        function getAdminNotificationBadgeCount() {
            var firstBadge = adminNotificationCountBadges.length ? adminNotificationCountBadges[0] : null;
            var parsedCount = firstBadge ? Number.parseInt(String(firstBadge.textContent || "0"), 10) : 0;

            if (!Number.isFinite(parsedCount) || parsedCount < 0) {
                return 0;
            }

            return parsedCount;
        }

        function setAdminNotificationBadgeCount(nextCount) {
            var normalizedCount = Number.parseInt(String(nextCount), 10);

            if (!Number.isFinite(normalizedCount) || normalizedCount < 0) {
                normalizedCount = 0;
            }

            adminNotificationCountBadges.forEach(function (badge) {
                badge.textContent = String(normalizedCount);
            });
        }

        function parseAdminNotificationAttachments(rawValue) {
            var parsed = [];

            try {
                var decoded = JSON.parse(String(rawValue || "[]"));

                if (Array.isArray(decoded)) {
                    parsed = decoded;
                }
            } catch (error) {
                parsed = [];
            }

            return parsed
                .map(function (entry) {
                    return String(entry || "").trim();
                })
                .filter(function (entry) {
                    return entry !== "";
                });
        }

        function addAdminNotificationCarriageReturns(textValue, maxLineLength) {
            var text = String(textValue || "");
            var limit = Number.parseInt(String(maxLineLength || 92), 10);

            if (!Number.isFinite(limit) || limit < 20) {
                limit = 92;
            }

            return text
                .replace(/\r\n/g, "\n")
                .replace(/\r/g, "\n")
                .split("\n")
                .map(function (line) {
                    if (line.length <= limit) {
                        return line;
                    }

                    var remaining = line;
                    var chunks = [];

                    while (remaining.length > limit) {
                        var segment = remaining.slice(0, limit);
                        var breakIndex = segment.lastIndexOf(" ");

                        if (breakIndex > Math.floor(limit * 0.5)) {
                            chunks.push(remaining.slice(0, breakIndex));
                            remaining = remaining.slice(breakIndex + 1);
                        } else {
                            chunks.push(segment);
                            remaining = remaining.slice(limit);
                        }
                    }

                    if (remaining !== "") {
                        chunks.push(remaining);
                    }

                    return chunks.join("\r\n");
                })
                .join("\r\n");
        }

        function setAdminNotificationReadState(item, isRead) {
            if (!item) {
                return;
            }

            var shouldRead = Boolean(isRead);

            item.setAttribute("data-notification-is-read", shouldRead ? "1" : "0");
            item.classList.toggle("is-read", shouldRead);
            item.classList.toggle("is-unread", !shouldRead);

            var unreadDot = item.querySelector(".admin-notification-unread-dot");
            if (unreadDot) {
                unreadDot.hidden = shouldRead;
            }
        }

        function clearAdminNotificationModalAttachments() {
            if (!adminNotificationModalAttachments) {
                return;
            }

            adminNotificationModalAttachments.innerHTML = "";
            adminNotificationModalAttachments.hidden = true;
        }

        function openAdminNotificationModal(item) {
            if (!item || !adminNotificationModal) {
                return;
            }

            var typeValue = String(item.getAttribute("data-notification-type") || "notification").trim();
            var titleValue = String(item.getAttribute("data-notification-title") || "Notification").trim();
            var summaryValue = String(item.getAttribute("data-notification-summary") || "").trim();
            var senderValue = String(item.getAttribute("data-notification-sender") || "System").trim();
            var emailValue = String(item.getAttribute("data-notification-email") || "").trim();
            var messageValue = String(item.getAttribute("data-notification-message") || "").trim();
            var timeValue = String(item.getAttribute("data-notification-created-at") || "Unknown time").trim();
            var attachments = parseAdminNotificationAttachments(item.getAttribute("data-notification-attachments") || "[]");
            var wrappedSummaryValue = addAdminNotificationCarriageReturns(summaryValue, 92);
            var wrappedMessageValue = addAdminNotificationCarriageReturns(messageValue, 92);

            if (adminNotificationModalTitle) {
                adminNotificationModalTitle.textContent = titleValue || "Notification";
            }

            if (adminNotificationModalType) {
                adminNotificationModalType.textContent = (typeValue || "notification").toUpperCase();
            }

            if (adminNotificationModalSender) {
                adminNotificationModalSender.textContent = senderValue || "System";
            }

            if (adminNotificationModalEmail) {
                adminNotificationModalEmail.textContent = emailValue;
                adminNotificationModalEmail.hidden = emailValue === "";
            }

            if (adminNotificationModalTime) {
                adminNotificationModalTime.textContent = timeValue || "Unknown time";
            }

            if (adminNotificationModalSummary) {
                if (typeValue !== "message" && summaryValue !== "") {
                    adminNotificationModalSummary.textContent = wrappedSummaryValue;
                    adminNotificationModalSummary.hidden = false;
                } else {
                    adminNotificationModalSummary.hidden = true;
                }
            }

            if (adminNotificationModalMessage) {
                if (messageValue !== "") {
                    adminNotificationModalMessage.textContent = wrappedMessageValue;
                    adminNotificationModalMessage.hidden = false;
                } else {
                    adminNotificationModalMessage.hidden = true;
                }
            }

            clearAdminNotificationModalAttachments();

            if (adminNotificationModalAttachments && attachments.length) {
                attachments.forEach(function (attachmentPath) {
                    var anchor = document.createElement("a");
                    anchor.className = "admin-notification-modal-attachment";
                    anchor.href = attachmentPath;
                    anchor.target = "_blank";
                    anchor.rel = "noopener noreferrer";

                    var image = document.createElement("img");
                    image.src = attachmentPath;
                    image.alt = "Notification attachment";

                    anchor.appendChild(image);
                    adminNotificationModalAttachments.appendChild(anchor);
                });

                adminNotificationModalAttachments.hidden = false;
            }

            if (adminNotificationModalEmpty) {
                var hasDetails = summaryValue !== "" || messageValue !== "" || attachments.length > 0;
                adminNotificationModalEmpty.hidden = hasDetails;
            }

            adminNotificationModal.hidden = false;
            document.body.classList.add("admin-modal-open");

            var isRead = String(item.getAttribute("data-notification-is-read") || "0") === "1";

            if (!isRead) {
                var notificationId = String(item.getAttribute("data-notification-id") || "").trim();

                if (!notificationId) {
                    return;
                }

                if (!adminNotificationMarkReadEndpoint) {
                    setAdminNotificationReadState(item, true);
                    setAdminNotificationBadgeCount(Math.max(0, getAdminNotificationBadgeCount() - 1));
                    return;
                }

                fetch(adminNotificationMarkReadEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        notificationId: notificationId
                    })
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            return {
                                ok: false
                            };
                        }).then(function (payload) {
                            return {
                                ok: response.ok,
                                payload: payload
                            };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.payload || !result.payload.ok) {
                            return;
                        }

                        setAdminNotificationReadState(item, true);

                        if (typeof result.payload.unreadCount !== "undefined") {
                            setAdminNotificationBadgeCount(result.payload.unreadCount);
                        } else {
                            setAdminNotificationBadgeCount(Math.max(0, getAdminNotificationBadgeCount() - 1));
                        }
                    })
                    .catch(function () {
                        // Keep UI usable even if mark-read network call fails.
                    });
            }
        }

        function closeAdminNotificationModal() {
            if (!adminNotificationModal) {
                return;
            }

            adminNotificationModal.hidden = true;
            document.body.classList.remove("admin-modal-open");
        }

        adminNotificationItems.forEach(function (item) {
            item.addEventListener("click", function () {
                var typeValue = String(item.getAttribute("data-notification-type") || "").toLowerCase().trim();

                if (typeValue === "order") {
                    var bookingsUrl = String(item.getAttribute("data-notification-bookings-url") || "").trim();

                    if (bookingsUrl !== "") {
                        window.location.href = bookingsUrl;
                        return;
                    }
                }

                openAdminNotificationModal(item);
            });
        });

        adminNotificationModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeAdminNotificationModal();
            });
        });

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape" || adminNotificationModal.hidden) {
                return;
            }

            closeAdminNotificationModal();
        });
    }

    function normalizeAdminNotificationCount(value) {
        var parsed = Number.parseInt(String(value || "0"), 10);

        if (!Number.isFinite(parsed) || parsed < 0) {
            return 0;
        }

        return parsed;
    }

    function setAllAdminNotificationBadgeCounts(nextCount) {
        var normalizedCount = normalizeAdminNotificationCount(nextCount);

        adminNotificationCountBadges.forEach(function (badge) {
            badge.textContent = String(normalizedCount);
        });
    }

    function resolveAdminNotificationLiveEndpoint() {
        if (adminNotificationLiveEndpoint) {
            return adminNotificationLiveEndpoint;
        }

        var firstTrigger = adminNotificationTriggers.length ? adminNotificationTriggers[0] : null;
        if (!firstTrigger) {
            return "";
        }

        var href = String(firstTrigger.getAttribute("href") || "").trim();
        if (!href) {
            return "";
        }

        href = href.split("#")[0];
        href = href.split("?")[0];

        if (href.slice(-1) !== "/") {
            href += "/";
        }

        adminNotificationLiveEndpoint = href + "live_updates.php";
        return adminNotificationLiveEndpoint;
    }

    function isAdminDashboardBookingsPanelActive() {
        var dashboardNav = document.querySelector("[data-admin-dashboard-nav]");

        if (!dashboardNav || !dashboardNav.classList.contains("is-swapped")) {
            return false;
        }

        var activePill = dashboardNav.querySelector("[data-admin-nav-pill].is-active");
        var activeTarget = activePill ? String(activePill.getAttribute("data-admin-panel-target") || "").toLowerCase() : "";

        return activeTarget === "bookings";
    }

    function applyAdminLiveNotificationsPayload(payload) {
        if (!payload || typeof payload !== "object") {
            return;
        }

        var unreadCount = normalizeAdminNotificationCount(payload.unreadCount);
        var unreadOrderCount = normalizeAdminNotificationCount(payload.unreadOrderCount);
        var latestOrderId = String(payload.latestOrderId || "").trim().toUpperCase();
        var ordersSignature = String(payload.ordersSignature || "").trim();
        var bookingsPanelActive = isAdminDashboardBookingsPanelActive();
        var shouldReloadBookings = false;

        setAllAdminNotificationBadgeCounts(unreadCount);

        if (bookingsPanelActive) {
            if (ordersSignature !== "" && adminLiveLastOrdersSignature !== "") {
                shouldReloadBookings = ordersSignature !== adminLiveLastOrdersSignature;
            } else if (latestOrderId !== "" && adminLiveLastOrderId !== "" && latestOrderId !== adminLiveLastOrderId) {
                shouldReloadBookings = true;
            }
        }

        if (shouldReloadBookings) {
            window.location.reload();
            return;
        }

        if (latestOrderId !== "") {
            adminLiveLastOrderId = latestOrderId;
        }

        if (ordersSignature !== "") {
            adminLiveLastOrdersSignature = ordersSignature;
        }

        adminLiveLastUnreadOrderCount = unreadOrderCount;
    }

    function pollAdminLiveNotifications() {
        var endpoint = resolveAdminNotificationLiveEndpoint();

        if (!endpoint || adminLivePollInFlight) {
            return;
        }

        var bookingsPanelActive = isAdminDashboardBookingsPanelActive();
        var requestUrl = endpoint
            + (endpoint.indexOf("?") >= 0 ? "&" : "?")
            + "t=" + encodeURIComponent(String(Date.now()))
            + "&clear_order=" + (bookingsPanelActive ? "1" : "0");

        adminLivePollInFlight = true;

        fetch(requestUrl, {
            method: "GET",
            headers: {
                Accept: "application/json"
            },
            credentials: "same-origin"
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false
                    };
                }).then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    return;
                }

                applyAdminLiveNotificationsPayload(result.payload);
            })
            .catch(function () {
                // Ignore polling failures and retry on next interval.
            })
            .finally(function () {
                adminLivePollInFlight = false;
            });
    }

    function initializeAdminLiveNotifications() {
        if (adminLivePollTimerId !== null) {
            return;
        }

        if (!adminNotificationTriggers.length && !adminNotificationCountBadges.length) {
            return;
        }

        if (!resolveAdminNotificationLiveEndpoint()) {
            return;
        }

        pollAdminLiveNotifications();
        adminLivePollTimerId = window.setInterval(pollAdminLiveNotifications, adminLivePollIntervalMs);

        var dashboardNav = document.querySelector("[data-admin-dashboard-nav]");

        if (dashboardNav) {
            dashboardNav.addEventListener("click", function (event) {
                var trigger = event.target.closest("[data-admin-nav-pill], [data-admin-nav-swap]");

                if (!trigger) {
                    return;
                }

                window.setTimeout(function () {
                    pollAdminLiveNotifications();
                }, 80);
            });
        }
    }

    function hideAdminUndoToast() {
        if (!adminUndoToast) {
            return;
        }

        adminUndoToast.classList.remove("is-visible");
        adminUndoToast.hidden = true;

        if (adminUndoAction) {
            adminUndoAction.disabled = false;
            adminUndoAction.textContent = "Undo";
        }

        if (adminUndoState.timerId) {
            window.clearTimeout(adminUndoState.timerId);
            adminUndoState.timerId = null;
        }

        adminUndoState.pending = null;
    }

    function formatArchiveDateLabel(isoValue) {
        var date = new Date(String(isoValue || ""));

        if (Number.isNaN(date.getTime())) {
            return String(isoValue || "");
        }

        return date.toLocaleString();
    }

    function showAdminUndoToast(message, pendingState) {
        if (!adminUndoToast || !adminUndoMessage || !adminUndoAction || !pendingState) {
            return;
        }

        if (adminUndoState.timerId) {
            window.clearTimeout(adminUndoState.timerId);
            adminUndoState.timerId = null;
        }

        adminUndoState.pending = pendingState;
        adminUndoMessage.textContent = message || "Archived";

        adminUndoToast.hidden = false;
        window.requestAnimationFrame(function () {
            adminUndoToast.classList.add("is-visible");
        });

        adminUndoState.timerId = window.setTimeout(function () {
            hideAdminUndoToast();
        }, 8000);
    }

    if (adminUndoAction) {
        adminUndoAction.addEventListener("click", function () {
            var pending = adminUndoState.pending;
            var endpoint = "";
            var fallbackError = "Unable to restore item.";

            if (!pending) {
                hideAdminUndoToast();
                return;
            }

            if (pending.type === "how") {
                endpoint = adminHowRestoreEndpoint;
                fallbackError = "Unable to restore How It Works image.";
            } else if (pending.type === "promo") {
                endpoint = adminPromoRestoreEndpoint;
                fallbackError = "Unable to restore promo banner.";
            } else if (pending.type === "event-package") {
                endpoint = adminEventRestoreEndpoint;
                fallbackError = "Unable to restore event package.";
            } else if (pending.type === "event-collection") {
                endpoint = adminEventCollectionRestoreEndpoint;
                fallbackError = "Unable to restore event collection.";
            } else {
                endpoint = adminRestoreEndpoint;
                fallbackError = "Unable to restore product.";
            }

            if (!endpoint) {
                hideAdminUndoToast();
                return;
            }

            adminUndoAction.disabled = true;
            adminUndoAction.textContent = "Restoring...";

            fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(
                    pending.type === "event-package"
                        ? { packageKey: pending.packageKey || pending.archiveKey || "" }
                        : { archiveKey: pending.archiveKey }
                )
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : fallbackError;
                        throw new Error(message);
                    }

                    if (pending.type === "how" || pending.type === "promo") {
                        if (pending.onRestore && typeof pending.onRestore === "function") {
                            pending.onRestore(result.payload);
                        }
                    } else if (pending.type === "event-package" && pending.card) {
                        pending.card.hidden = false;
                        pending.card.classList.remove("is-hidden");
                        pending.card.classList.remove("is-admin-removing");
                    } else if (pending.type === "event-collection" && pending.card) {
                        pending.card.hidden = false;
                        pending.card.classList.remove("is-hidden");
                        pending.card.classList.remove("is-admin-removing");
                        updateAdminEventCollectionSectionState(pending.card.closest(".event-category-section"));
                        syncAdminEventCollectionEmptyState();
                    } else if (pending.card) {
                        pending.card.removeAttribute("data-admin-removed");
                        pending.card.classList.remove("is-hidden");
                        pending.card.classList.remove("is-admin-removing");

                        if (result.payload.restoredKey) {
                            pending.card.setAttribute("data-product-key", result.payload.restoredKey);
                        }
                    }

                    if (pending.button) {
                        pending.button.disabled = false;
                    }

                    if (pending.type === "product") {
                        applyProductFilters();
                    }

                    hideAdminUndoToast();
                })
                .catch(function (error) {
                    var restoreErrorMessage = error.message || fallbackError;

                    if (adminActionModalBackdrop && adminActionModalTitle && adminActionModalMessage && adminActionModalConfirm) {
                        openAdminActionModal({
                            title: "Restore Failed",
                            message: restoreErrorMessage,
                            confirmLabel: "OK"
                        });
                    } else if (adminUndoToast && adminUndoMessage) {
                        adminUndoToast.hidden = false;
                        adminUndoToast.classList.add("is-visible");
                        adminUndoMessage.textContent = restoreErrorMessage;
                    }

                    adminUndoAction.disabled = false;
                    adminUndoAction.textContent = "Undo";
                });
        });
    }

    function archiveFeaturedProduct(card, button, archiveEndpoint) {
        if (!card || !button || !archiveEndpoint) {
            return;
        }

        var productKey = card.getAttribute("data-product-key") || "";
        if (!productKey) {
            return;
        }

        card.classList.add("is-admin-removing");
        button.disabled = true;

        fetch(archiveEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                productKey: productKey
            })
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive product.";
                    throw new Error(message);
                }

                var titleLink = card.querySelector(".product-title-link");
                var productName = titleLink ? titleLink.textContent.trim() : "Product";
                var archivedAt = result.payload.archivedEntry && result.payload.archivedEntry.archivedAt
                    ? String(result.payload.archivedEntry.archivedAt)
                    : "";
                var archiveKey = result.payload.archivedEntry && result.payload.archivedEntry.archiveKey
                    ? String(result.payload.archivedEntry.archiveKey)
                    : "";

                window.setTimeout(function () {
                    card.setAttribute("data-admin-removed", "true");
                    card.classList.remove("is-admin-removing");
                    card.classList.add("is-hidden");
                    applyProductFilters();

                    if (archiveKey) {
                        showAdminUndoToast(productName + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""), {
                            type: "product",
                            archiveKey: archiveKey,
                            card: card,
                            button: button
                        });
                    }
                }, 160);
            })
            .catch(function (error) {
                openAdminActionModal({
                    title: "Archive Failed",
                    message: error.message || "Unable to archive product.",
                    confirmLabel: "OK"
                });
                card.classList.remove("is-admin-removing");
                button.disabled = false;
            });
    }

    adminRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest(".product-card");
            var archiveEndpoint = adminEditBackdrop ? (adminEditBackdrop.getAttribute("data-admin-archive-endpoint") || "") : "";

            if (!card || !archiveEndpoint) {
                return;
            }

            var titleLink = card.querySelector(".product-title-link");
            var productName = titleLink ? titleLink.textContent.trim() : "This featured product";

            openAdminActionModal({
                title: "Archive Featured Product",
                message: productName + " will be archived together with all of its equipment inventory units.",
                confirmLabel: "Archive Product",
                onConfirm: function () {
                    archiveFeaturedProduct(card, button, archiveEndpoint);
                }
            });
        });
    });

    function archiveEventPackage(card, button) {
        if (!card || !button || !adminEventArchiveEndpoint) {
            return;
        }

        var packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        if (!packageKey) {
            return;
        }

        card.classList.add("is-admin-removing");
        button.disabled = true;

        fetch(adminEventArchiveEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                packageKey: packageKey
            })
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive event package.";
                    throw new Error(message);
                }

                var packageTitle = String(card.getAttribute("data-admin-event-package-title") || "Event package").trim();
                var archivedAt = result.payload.archivedEntry && result.payload.archivedEntry.archivedAt
                    ? String(result.payload.archivedEntry.archivedAt)
                    : "";

                window.setTimeout(function () {
                    card.classList.remove("is-admin-removing");
                    card.classList.add("is-hidden");
                    card.hidden = true;

                    showAdminUndoToast(packageTitle + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""), {
                        type: "event-package",
                        packageKey: packageKey,
                        archiveKey: packageKey,
                        card: card,
                        button: button
                    });
                }, 160);
            })
            .catch(function (error) {
                openAdminActionModal({
                    title: "Archive Failed",
                    message: error.message || "Unable to archive event package.",
                    confirmLabel: "OK"
                });
                card.classList.remove("is-admin-removing");
                button.disabled = false;
            });
    }

    adminEventRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest("[data-admin-event-package]");

            if (!card || !adminEventArchiveEndpoint) {
                return;
            }

            archiveEventPackage(card, button);
        });
    });

    function updateAdminEventCollectionSectionState(section) {
        if (!section) {
            return;
        }

        var cards = section.querySelectorAll("[data-admin-event-collection-card]");
        var visibleCount = 0;

        cards.forEach(function (card) {
            if (!card.hidden && !card.classList.contains("is-hidden")) {
                visibleCount += 1;
            }
        });

        var countLabel = section.querySelector("[data-admin-event-category-count]");
        if (countLabel) {
            var singularLabel = adminEventCollectionCountSingular || "event";
            var pluralLabel = adminEventCollectionCountPlural || (singularLabel + "s");
            countLabel.textContent = String(visibleCount) + " " + (visibleCount === 1 ? singularLabel : pluralLabel);
        }

        section.hidden = visibleCount === 0;
    }

    function syncAdminEventCollectionEmptyState() {
        if (!adminEventCollectionsShell || !adminEventCollectionsEmptyState) {
            return;
        }

        var visibleSections = adminEventCollectionsShell.querySelectorAll(".event-category-section:not([hidden])");
        adminEventCollectionsEmptyState.hidden = visibleSections.length > 0;
    }

    function archiveEventCollection(card, button) {
        if (!card || !button || !adminEventCollectionArchiveEndpoint) {
            return;
        }

        var packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        var collectionFolder = String(card.getAttribute("data-admin-event-collection-folder") || "").trim();
        var categoryLabel = String(card.getAttribute("data-admin-event-collection-category") || "").trim();
        var collectionName = String(card.getAttribute("data-admin-event-collection-name") || "Collection").trim();

        if (!packageKey || !collectionFolder) {
            return;
        }

        card.classList.add("is-admin-removing");
        button.disabled = true;

        fetch(adminEventCollectionArchiveEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                packageKey: packageKey,
                collectionFolder: collectionFolder,
                categoryLabel: categoryLabel,
                collectionLabel: collectionName
            })
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive collection.";
                    throw new Error(message);
                }

                var archivedEntry = result.payload.archivedEntry || {};
                var archivedAt = archivedEntry.archivedAt ? String(archivedEntry.archivedAt) : "";
                var archiveKey = archivedEntry.archiveKey ? String(archivedEntry.archiveKey) : "";

                window.setTimeout(function () {
                    card.classList.remove("is-admin-removing");
                    card.classList.add("is-hidden");
                    card.hidden = true;

                    updateAdminEventCollectionSectionState(card.closest(".event-category-section"));
                    syncAdminEventCollectionEmptyState();

                    showAdminUndoToast(collectionName + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""), {
                        type: "event-collection",
                        archiveKey: archiveKey,
                        card: card,
                        button: button
                    });
                }, 160);
            })
            .catch(function (error) {
                openAdminActionModal({
                    title: "Archive Failed",
                    message: error.message || "Unable to archive collection.",
                    confirmLabel: "OK"
                });
                card.classList.remove("is-admin-removing");
                button.disabled = false;
            });
    }

    if (adminEventCollectionCards.length) {
        var updatedSections = [];

        adminEventCollectionCards.forEach(function (card) {
            var section = card.closest(".event-category-section");

            if (!section || updatedSections.indexOf(section) >= 0) {
                return;
            }

            updatedSections.push(section);
            updateAdminEventCollectionSectionState(section);
        });

        syncAdminEventCollectionEmptyState();
    }

    function setAdminEventCollectionCreateSavingState(isSaving) {
        var saving = Boolean(isSaving);

        adminEventCollectionCreateState.isSaving = saving;

        if (adminEventCollectionCreateSave) {
            adminEventCollectionCreateSave.disabled = saving;
            adminEventCollectionCreateSave.textContent = saving ? "Creating..." : "Create Collection";
        }

        if (adminEventCollectionCreateCancel) {
            adminEventCollectionCreateCancel.disabled = saving;
        }

        if (adminEventCollectionCreateClose) {
            adminEventCollectionCreateClose.disabled = saving;
        }

        if (adminEventCollectionCreateOpenButton) {
            adminEventCollectionCreateOpenButton.disabled = saving;
        }

        if (adminEventCollectionCreateCategoryInput) {
            adminEventCollectionCreateCategoryInput.disabled = saving;
        }

        if (adminEventCollectionCreateNameInput) {
            adminEventCollectionCreateNameInput.disabled = saving;
        }
    }

    function setAdminEventCollectionCreateFeedback(message, type) {
        if (!adminEventCollectionCreateFeedback) {
            return;
        }

        var normalizedMessage = String(message || "").trim();
        var normalizedType = String(type || "").toLowerCase().trim();

        adminEventCollectionCreateFeedback.classList.remove("is-error", "is-success");
        adminEventCollectionCreateFeedback.textContent = normalizedMessage;
        adminEventCollectionCreateFeedback.hidden = normalizedMessage === "";

        if (normalizedMessage !== "" && (normalizedType === "error" || normalizedType === "success")) {
            adminEventCollectionCreateFeedback.classList.add("is-" + normalizedType);
        }
    }

    function resetAdminEventCollectionCreateState() {
        if (adminEventCollectionCreateCategoryInput) {
            adminEventCollectionCreateCategoryInput.value = "";
        }

        if (adminEventCollectionCreateNameInput) {
            adminEventCollectionCreateNameInput.value = "";
        }

        setAdminEventCollectionCreateFeedback("", "error");
        setAdminEventCollectionCreateSavingState(false);
    }

    function closeAdminEventCollectionCreateModal(forceClose) {
        if (!adminEventCollectionCreateBackdrop || (adminEventCollectionCreateState.isSaving && !forceClose)) {
            return;
        }

        adminEventCollectionCreateBackdrop.hidden = true;
        resetAdminEventCollectionCreateState();
        syncAdminModalBodyLock();
    }

    function openAdminEventCollectionCreateModal() {
        if (!adminEventCollectionCreateBackdrop) {
            return;
        }

        resetAdminEventCollectionCreateState();
        adminEventCollectionCreateBackdrop.hidden = false;
        syncAdminModalBodyLock();

        if (adminEventCollectionCreateCategoryInput) {
            adminEventCollectionCreateCategoryInput.focus();
        }
    }

    function parseAdminJsonResponse(response) {
        return response.text().then(function (rawText) {
            var normalizedText = String(rawText || "").trim();
            var payload = null;

            if (normalizedText !== "") {
                try {
                    payload = JSON.parse(normalizedText);
                } catch (error) {
                    payload = null;
                }
            }

            return {
                ok: Boolean(response && response.ok),
                payload: payload,
                status: Number(response && response.status),
                rawText: normalizedText
            };
        });
    }

    function buildAdminJsonResponseError(result, fallbackMessage) {
        var fallback = String(fallbackMessage || "Request failed.").trim() || "Request failed.";

        if (result && result.payload && typeof result.payload === "object") {
            var payloadMessage = String(result.payload.message || "").trim();

            if (payloadMessage) {
                return payloadMessage;
            }
        }

        if (result && result.rawText) {
            var compactText = String(result.rawText || "").replace(/\s+/g, " ").trim();

            if (compactText) {
                if (compactText.length > 240) {
                    compactText = compactText.slice(0, 240) + "...";
                }

                return compactText;
            }
        }

        if (result && Number.isFinite(result.status) && result.status > 0) {
            return fallback + " (HTTP " + String(result.status) + ")";
        }

        return fallback;
    }

    function submitAdminEventCollectionCreate() {
        if (adminEventCollectionCreateState.isSaving) {
            return;
        }

        var packageKey = String(adminEventCollectionDefaultPackageKey || "").trim();
        var categoryLabel = String(adminEventCollectionCreateCategoryInput ? adminEventCollectionCreateCategoryInput.value : "").trim();
        var collectionLabel = String(adminEventCollectionCreateNameInput ? adminEventCollectionCreateNameInput.value : "").trim();

        if (!packageKey) {
            setAdminEventCollectionCreateFeedback("Event package key is unavailable.", "error");
            return;
        }

        if (!categoryLabel) {
            setAdminEventCollectionCreateFeedback("Main Tag is required.", "error");
            return;
        }

        if (!collectionLabel) {
            setAdminEventCollectionCreateFeedback("Collection Name is required.", "error");
            return;
        }

        if (!adminEventCollectionCreateEndpoint) {
            setAdminEventCollectionCreateFeedback("Collection create endpoint is unavailable.", "error");
            return;
        }

        setAdminEventCollectionCreateFeedback("", "error");
        setAdminEventCollectionCreateSavingState(true);

        fetch(adminEventCollectionCreateEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                packageKey: packageKey,
                packageFolder: String(adminEventCollectionDefaultPackageFolder || "").trim(),
                categoryLabel: categoryLabel,
                collectionLabel: collectionLabel
            })
        })
            .then(parseAdminJsonResponse)
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    var message = buildAdminJsonResponseError(result, "Unable to create collection.");
                    throw new Error(message);
                }

                closeAdminEventCollectionCreateModal(true);
                window.location.reload();
            })
            .catch(function (error) {
                setAdminEventCollectionCreateFeedback(error.message || "Unable to create collection.", "error");
            })
            .finally(function () {
                setAdminEventCollectionCreateSavingState(false);
            });
    }

    function normalizeAdminEventCollectionImagePath(value) {
        return String(value || "")
            .replace(/\\/g, "/")
            .replace(/^\/+/, "")
            .trim();
    }

    function resolveAdminEventCollectionMediaTypeFromPath(pathValue) {
        var normalizedPath = normalizeAdminEventCollectionImagePath(pathValue);
        var extensionMatch = normalizedPath.match(/\.([a-z0-9]+)(?:[?#].*)?$/i);
        var extension = extensionMatch ? String(extensionMatch[1] || "").toLowerCase() : "";

        if (/^(mp4|webm|ogg)$/.test(extension)) {
            return "video";
        }

        return "image";
    }

    function resolveAdminEventCollectionMediaTypeFromMime(mimeType) {
        var normalizedMime = String(mimeType || "").toLowerCase().trim();

        if (normalizedMime.indexOf("video/") === 0) {
            return "video";
        }

        return "image";
    }

    function buildAdminEventCollectionImageUrl(pathValue) {
        var normalizedPath = normalizeAdminEventCollectionImagePath(pathValue);

        if (!normalizedPath) {
            return "";
        }

        if (/^(?:https?:)?\/\//i.test(normalizedPath) || normalizedPath.indexOf("data:image/") === 0 || normalizedPath.indexOf("data:video/") === 0) {
            return normalizedPath;
        }

        var encodedPath = normalizedPath
            .split("/")
            .map(function (segment) {
                return encodeURIComponent(segment);
            })
            .join("/");
        var base = String(adminEventCollectionAssetBase || "").trim();

        if (base) {
            return base.replace(/\/?$/, "/") + encodedPath;
        }

        return "/" + encodedPath;
    }

    function setAdminEventCollectionEditSavingState(isSaving) {
        var saving = Boolean(isSaving);

        adminEventCollectionEditState.isSaving = saving;

        if (adminEventCollectionEditSave) {
            adminEventCollectionEditSave.disabled = saving;
            adminEventCollectionEditSave.textContent = saving ? "Saving..." : "Save Changes";
        }

        if (adminEventCollectionEditCancel) {
            adminEventCollectionEditCancel.disabled = saving;
        }

        if (adminEventCollectionEditClose) {
            adminEventCollectionEditClose.disabled = saving;
        }

        if (adminEventCollectionAddTrigger) {
            adminEventCollectionAddTrigger.disabled = saving;
        }

        if (adminEventCollectionEditCategoryInput) {
            adminEventCollectionEditCategoryInput.disabled = saving;
        }

        if (adminEventCollectionEditNameInput) {
            adminEventCollectionEditNameInput.disabled = saving;
        }
    }

    function setAdminEventCollectionEditFeedback(message, type) {
        if (!adminEventCollectionEditFeedback) {
            return;
        }

        var normalizedMessage = String(message || "").trim();
        var normalizedType = String(type || "").toLowerCase().trim();

        adminEventCollectionEditFeedback.classList.remove("is-error", "is-success");
        adminEventCollectionEditFeedback.textContent = normalizedMessage;
        adminEventCollectionEditFeedback.hidden = normalizedMessage === "";

        if (normalizedMessage !== "" && (normalizedType === "error" || normalizedType === "success")) {
            adminEventCollectionEditFeedback.classList.add("is-" + normalizedType);
        }
    }

    function revokeAdminEventCollectionObjectUrl(imageEntry) {
        if (!imageEntry || typeof imageEntry !== "object") {
            return;
        }

        var previewUrl = String(imageEntry.previewUrl || "").trim();
        var shouldRevoke = Boolean(imageEntry.isObjectUrl)
            || (previewUrl.indexOf("blob:") === 0 && imageEntry.isNew);

        if (!shouldRevoke || previewUrl === "") {
            return;
        }

        try {
            window.URL.revokeObjectURL(previewUrl);
        } catch (error) {
            // Ignore URL revocation failures and keep modal workflow uninterrupted.
        }

        imageEntry.isObjectUrl = false;
    }

    function revokeAdminEventCollectionObjectUrls(imageEntries) {
        if (!Array.isArray(imageEntries) || !imageEntries.length) {
            return;
        }

        imageEntries.forEach(function (entry) {
            revokeAdminEventCollectionObjectUrl(entry);
        });
    }

    function resetAdminEventCollectionEditState() {
        revokeAdminEventCollectionObjectUrls(adminEventCollectionEditState.images);

        adminEventCollectionEditState.card = null;
        adminEventCollectionEditState.packageKey = "";
        adminEventCollectionEditState.packageFolder = "";
        adminEventCollectionEditState.collectionFolder = "";
        adminEventCollectionEditState.collectionName = "";
        adminEventCollectionEditState.categoryLabel = "";
        adminEventCollectionEditState.images = [];
        adminEventCollectionEditState.isSaving = false;
        adminEventCollectionEditState.tempCounter = 0;

        if (adminEventCollectionEditPackageKeyInput) {
            adminEventCollectionEditPackageKeyInput.value = "";
        }

        if (adminEventCollectionEditPackageFolderInput) {
            adminEventCollectionEditPackageFolderInput.value = "";
        }

        if (adminEventCollectionEditCollectionFolderInput) {
            adminEventCollectionEditCollectionFolderInput.value = "";
        }

        if (adminEventCollectionEditCategoryInput) {
            adminEventCollectionEditCategoryInput.value = "";
        }

        if (adminEventCollectionEditNameInput) {
            adminEventCollectionEditNameInput.value = "";
        }

        if (adminEventCollectionEditGrid) {
            adminEventCollectionEditGrid.innerHTML = "";
        }

        if (adminEventCollectionAddInput) {
            adminEventCollectionAddInput.value = "";
        }

        if (adminEventCollectionEditTitle) {
            adminEventCollectionEditTitle.textContent = "Edit Collection";
        }

        if (adminEventCollectionEditEmpty) {
            adminEventCollectionEditEmpty.hidden = true;
        }

        setAdminEventCollectionEditFeedback("", "error");
        setAdminEventCollectionEditSavingState(false);
    }

    function renderAdminEventCollectionEditGrid() {
        if (!adminEventCollectionEditGrid) {
            return;
        }

        adminEventCollectionEditGrid.innerHTML = "";

        adminEventCollectionEditState.images.forEach(function (imageEntry, imageIndex) {
            var tile = document.createElement("article");
            tile.className = "admin-event-collection-image-item";
            tile.setAttribute("data-admin-event-collection-image-index", String(imageIndex));

            if (imageEntry.isExcluded) {
                tile.classList.add("is-excluded");
            }

            var toggleButton = document.createElement("button");
            toggleButton.type = "button";
            toggleButton.className = "admin-event-collection-image-toggle";
            toggleButton.setAttribute("data-admin-event-collection-toggle", String(imageIndex));
            toggleButton.setAttribute("aria-label", imageEntry.isExcluded ? "Undo exclusion" : "Exclude image");

            if (imageEntry.isExcluded) {
                toggleButton.classList.add("is-undo");
                toggleButton.innerHTML = "&#10003;";
            } else {
                toggleButton.innerHTML = "&times;";
            }

            var mediaType = String(imageEntry.mediaType || resolveAdminEventCollectionMediaTypeFromPath(imageEntry.imagePath || imageEntry.previewUrl || "image")).toLowerCase() === "video"
                ? "video"
                : "image";
            var mediaElement;

            if (mediaType === "video") {
                mediaElement = document.createElement("video");
                mediaElement.src = String(imageEntry.previewUrl || "");
                mediaElement.controls = true;
                mediaElement.muted = true;
                mediaElement.playsInline = true;
                mediaElement.preload = "metadata";
            } else {
                mediaElement = document.createElement("img");
                mediaElement.src = String(imageEntry.previewUrl || "");
                mediaElement.alt = String(imageEntry.alt || "Collection image");
                mediaElement.loading = "lazy";
            }

            var metaLabel = document.createElement("span");
            metaLabel.className = "admin-event-collection-image-meta";
            metaLabel.textContent = String(imageEntry.label || ("Image " + String(imageIndex + 1)));

            tile.appendChild(toggleButton);
            tile.appendChild(mediaElement);
            tile.appendChild(metaLabel);
            adminEventCollectionEditGrid.appendChild(tile);
        });

        if (adminEventCollectionEditEmpty) {
            adminEventCollectionEditEmpty.hidden = adminEventCollectionEditState.images.length > 0;
        }
    }

    function applyAdminEventCollectionImagesToCard(card, imageEntries) {
        if (!card) {
            return;
        }

        var activeImages = (Array.isArray(imageEntries) ? imageEntries : []).filter(function (entry) {
            return !entry.isExcluded;
        });
        var collectionName = String(card.getAttribute("data-admin-event-collection-name") || "Collection").trim() || "Collection";
        var masonry = card.querySelector(".event-photo-masonry");
        var emptyState = card.querySelector(".event-card-empty");

        if (activeImages.length === 0) {
            if (masonry) {
                masonry.remove();
            }

            if (!emptyState) {
                emptyState = document.createElement("div");
                emptyState.className = "event-card-empty";
                emptyState.textContent = "No media were found for this collection yet.";
                card.appendChild(emptyState);
            }

            return;
        }

        if (!masonry) {
            masonry = document.createElement("div");
            masonry.className = "event-photo-masonry";
            card.appendChild(masonry);
        }

        masonry.setAttribute("aria-label", collectionName + " gallery");
        masonry.innerHTML = "";

        if (emptyState) {
            emptyState.remove();
        }

        activeImages.forEach(function (entry, index) {
            var figure = document.createElement("figure");
            figure.className = "event-photo-item";

            var mediaType = String(entry.mediaType || resolveAdminEventCollectionMediaTypeFromPath(entry.imagePath || entry.previewUrl || "image")).toLowerCase() === "video"
                ? "video"
                : "image";
            var mediaElement;

            if (mediaType === "video") {
                mediaElement = document.createElement("video");
                mediaElement.src = String(entry.previewUrl || "");
                mediaElement.controls = true;
                mediaElement.muted = true;
                mediaElement.playsInline = true;
                mediaElement.preload = "metadata";
                mediaElement.setAttribute("data-admin-event-media-type", "video");
            } else {
                mediaElement = document.createElement("img");
                mediaElement.src = String(entry.previewUrl || "");
                mediaElement.alt = collectionName + " media " + String(index + 1);
                mediaElement.loading = "lazy";
                mediaElement.setAttribute("data-admin-event-media-type", "image");
            }

            if (entry.imagePath) {
                mediaElement.setAttribute("data-admin-event-image-path", String(entry.imagePath));
            }

            figure.appendChild(mediaElement);
            masonry.appendChild(figure);
        });
    }

    function closeAdminEventCollectionEditModal(forceClose) {
        if (!adminEventCollectionEditBackdrop || (adminEventCollectionEditState.isSaving && !forceClose)) {
            return;
        }

        adminEventCollectionEditBackdrop.hidden = true;
        resetAdminEventCollectionEditState();
        syncAdminModalBodyLock();
    }

    function openAdminEventCollectionEditModal(card) {
        if (!adminEventCollectionEditBackdrop || !card) {
            return;
        }

        resetAdminEventCollectionEditState();

        adminEventCollectionEditState.card = card;
        adminEventCollectionEditState.packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        adminEventCollectionEditState.packageFolder = String(card.getAttribute("data-admin-event-package-folder") || "").trim();
        adminEventCollectionEditState.collectionFolder = String(card.getAttribute("data-admin-event-collection-folder") || "").trim();
        adminEventCollectionEditState.collectionName = String(card.getAttribute("data-admin-event-collection-name") || "Collection").trim() || "Collection";
        adminEventCollectionEditState.categoryLabel = String(card.getAttribute("data-admin-event-collection-category") || "").trim();

        if (adminEventCollectionEditPackageKeyInput) {
            adminEventCollectionEditPackageKeyInput.value = adminEventCollectionEditState.packageKey;
        }

        if (adminEventCollectionEditPackageFolderInput) {
            adminEventCollectionEditPackageFolderInput.value = adminEventCollectionEditState.packageFolder;
        }

        if (adminEventCollectionEditCollectionFolderInput) {
            adminEventCollectionEditCollectionFolderInput.value = adminEventCollectionEditState.collectionFolder;
        }

        if (adminEventCollectionEditCategoryInput) {
            adminEventCollectionEditCategoryInput.value = adminEventCollectionEditState.categoryLabel;
        }

        if (adminEventCollectionEditNameInput) {
            adminEventCollectionEditNameInput.value = adminEventCollectionEditState.collectionName;
        }

        if (adminEventCollectionEditTitle) {
            adminEventCollectionEditTitle.textContent = "Edit Collection: " + adminEventCollectionEditState.collectionName;
        }

        var imageNodes = card.querySelectorAll(".event-photo-item [data-admin-event-image-path]");

        imageNodes.forEach(function (imageNode, imageIndex) {
            var imagePath = normalizeAdminEventCollectionImagePath(imageNode.getAttribute("data-admin-event-image-path") || "");
            var mediaType = String(imageNode.getAttribute("data-admin-event-media-type") || imageNode.tagName || "").toLowerCase();
            if (mediaType !== "video") {
                mediaType = (String(imageNode.tagName || "").toLowerCase() === "video") ? "video" : "image";
            }
            var imageUrl = String(imageNode.getAttribute("src") || imageNode.getAttribute("poster") || "").trim();
            var imageLabel = imagePath ? imagePath.split("/").pop() : ("Image " + String(imageIndex + 1));

            adminEventCollectionEditState.images.push({
                id: "existing-" + String(imageIndex + 1),
                tempId: "",
                imagePath: imagePath,
                previewUrl: imageUrl,
                alt: String(imageNode.getAttribute("alt") || "Collection media"),
                label: imageLabel,
                fileName: imageLabel,
                dataUrl: "",
                mediaType: mediaType,
                isNew: false,
                isArchived: false,
                isExcluded: false
            });
        });

        var activeImagePathLookup = Object.create(null);

        adminEventCollectionEditState.images.forEach(function (imageEntry) {
            var normalizedPath = normalizeAdminEventCollectionImagePath(imageEntry.imagePath || "");

            if (normalizedPath) {
                activeImagePathLookup[normalizedPath.toLowerCase()] = true;
            }
        });

        var archivedImagePathsRaw = String(card.getAttribute("data-admin-event-collection-archived-images") || "[]");
        var archivedImagePaths = [];

        try {
            var decodedArchivedPaths = JSON.parse(archivedImagePathsRaw);

            if (Array.isArray(decodedArchivedPaths)) {
                archivedImagePaths = decodedArchivedPaths;
            }
        } catch (error) {
            archivedImagePaths = [];
        }

        archivedImagePaths.forEach(function (archivedPath, archivedIndex) {
            var normalizedArchivedPath = normalizeAdminEventCollectionImagePath(archivedPath);
            var pathKey = normalizedArchivedPath.toLowerCase();

            if (!normalizedArchivedPath || activeImagePathLookup[pathKey]) {
                return;
            }

            activeImagePathLookup[pathKey] = true;

            var archivedFileName = normalizedArchivedPath.split("/").pop() || ("Archived image " + String(archivedIndex + 1));

            adminEventCollectionEditState.images.push({
                id: "archived-" + String(archivedIndex + 1),
                tempId: "",
                imagePath: normalizedArchivedPath,
                previewUrl: buildAdminEventCollectionImageUrl(normalizedArchivedPath),
                alt: adminEventCollectionEditState.collectionName + " archived image",
                label: archivedFileName + " (Archived)",
                fileName: archivedFileName,
                dataUrl: "",
                mediaType: resolveAdminEventCollectionMediaTypeFromPath(normalizedArchivedPath),
                isNew: false,
                isArchived: true,
                isExcluded: true
            });
        });

        renderAdminEventCollectionEditGrid();
        adminEventCollectionEditBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function readAdminEventCollectionFileAsDataUrl(file) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            var mimeType = String(file && file.type ? file.type : "").toLowerCase();
            var fileName = String(file && file.name ? file.name : "").toLowerCase();
            var isImage = /^image\/(png|jpeg|jpg|webp|gif)$/.test(mimeType);
            var isVideo = /^video\/(mp4|webm|ogg)$/.test(mimeType);

            if (!isImage && !isVideo) {
                isImage = /\.(png|jpe?g|webp|gif)$/i.test(fileName);
                isVideo = /\.(mp4|webm|ogg)$/i.test(fileName);
            }

            if (!isImage && !(adminEventCollectionAllowVideo && isVideo)) {
                reject(new Error(adminEventCollectionAllowVideo
                    ? "Only PNG, JPG, WEBP, GIF, MP4, WEBM, or OGG files are allowed."
                    : "Only image files are allowed."));
                return;
            }

            reader.onload = function (event) {
                var dataUrl = String(event && event.target && event.target.result ? event.target.result : "");

                if (dataUrl.indexOf("data:image/") !== 0 && dataUrl.indexOf("data:video/") !== 0) {
                    reject(new Error("Only image or video files are allowed."));
                    return;
                }

                resolve(dataUrl);
            };

            reader.onerror = function () {
                reject(new Error("Unable to read one of the selected media files."));
            };

            reader.readAsDataURL(file);
        });
    }

    function handleAdminEventCollectionAddFiles(fileList) {
        var files = Array.prototype.slice.call(fileList || []);

        if (!files.length) {
            return;
        }

        var acceptedFiles = files.filter(function (file) {
            var mimeType = String(file && file.type ? file.type : "").toLowerCase();
            var fileName = String(file && file.name ? file.name : "").toLowerCase();

            if (!mimeType) {
                if (/\.(png|jpe?g|webp|gif)$/i.test(fileName)) {
                    return true;
                }

                return adminEventCollectionAllowVideo && /\.(mp4|webm|ogg)$/i.test(fileName);
            }

            if (/^image\/(png|jpeg|jpg|webp|gif)$/.test(mimeType)) {
                return true;
            }

            return adminEventCollectionAllowVideo && /^video\/(mp4|webm|ogg)$/.test(mimeType);
        });

        if (!acceptedFiles.length) {
            setAdminEventCollectionEditFeedback(
                adminEventCollectionAllowVideo
                    ? "Please select valid files (PNG, JPG, WEBP, GIF, MP4, WEBM, or OGG)."
                    : "Please select valid image files (PNG, JPG, WEBP, or GIF).",
                "error"
            );
            return;
        }

        acceptedFiles.forEach(function (file) {
            adminEventCollectionEditState.tempCounter += 1;

            var fileName = String(file && file.name ? file.name : ("new-image-" + String(adminEventCollectionEditState.tempCounter) + ".png"));
            var previewUrl = "";
            var mediaType = resolveAdminEventCollectionMediaTypeFromMime(String(file && file.type ? file.type : ""));

            if (mediaType !== "video" && /\.(mp4|webm|ogg)$/i.test(fileName)) {
                mediaType = "video";
            }

            try {
                if (window.URL && typeof window.URL.createObjectURL === "function") {
                    previewUrl = String(window.URL.createObjectURL(file) || "");
                }
            } catch (error) {
                previewUrl = "";
            }

            adminEventCollectionEditState.images.push({
                id: "new-" + String(adminEventCollectionEditState.tempCounter),
                tempId: "new-" + String(adminEventCollectionEditState.tempCounter),
                imagePath: "",
                previewUrl: previewUrl,
                alt: adminEventCollectionEditState.collectionName + " new media",
                label: fileName + " (New)",
                fileName: fileName,
                file: file,
                dataUrl: "",
                mediaType: mediaType,
                isObjectUrl: previewUrl.indexOf("blob:") === 0,
                isNew: true,
                isExcluded: false
            });
        });

        setAdminEventCollectionEditFeedback("", "error");
        renderAdminEventCollectionEditGrid();
    }

    function submitAdminEventCollectionEditChanges() {
        if (adminEventCollectionEditState.isSaving) {
            return;
        }

        var card = adminEventCollectionEditState.card;
        var packageKey = adminEventCollectionEditState.packageKey;
        var collectionFolder = adminEventCollectionEditState.collectionFolder;
        var categoryLabel = adminEventCollectionEditState.categoryLabel;
        var collectionName = adminEventCollectionEditState.collectionName;
        var nextCategoryLabel = String(adminEventCollectionEditCategoryInput ? adminEventCollectionEditCategoryInput.value : categoryLabel).trim();
        var nextCollectionName = String(adminEventCollectionEditNameInput ? adminEventCollectionEditNameInput.value : collectionName).trim();

        if (!card || !packageKey || !collectionFolder) {
            closeAdminEventCollectionEditModal();
            return;
        }

        if (!nextCategoryLabel) {
            setAdminEventCollectionEditFeedback("Main Tag is required.", "error");
            return;
        }

        if (!nextCollectionName) {
            setAdminEventCollectionEditFeedback("Collection Name is required.", "error");
            return;
        }

        var hasLabelChanges = nextCategoryLabel !== categoryLabel || nextCollectionName !== collectionName;

        adminEventCollectionEditState.categoryLabel = nextCategoryLabel;
        adminEventCollectionEditState.collectionName = nextCollectionName;

        var excludedImagePaths = [];
        var restoreImagePaths = [];
        var addedImageEntries = [];

        adminEventCollectionEditState.images.forEach(function (imageEntry) {
            if (imageEntry.isNew) {
                if (!imageEntry.isExcluded) {
                    addedImageEntries.push(imageEntry);
                }

                return;
            }

            if (imageEntry.isArchived) {
                if (!imageEntry.isExcluded && imageEntry.imagePath) {
                    restoreImagePaths.push(String(imageEntry.imagePath));
                }

                return;
            }

            if (imageEntry.isExcluded && imageEntry.imagePath) {
                excludedImagePaths.push(String(imageEntry.imagePath));
            }
        });

        if (!hasLabelChanges && !excludedImagePaths.length && !restoreImagePaths.length && !addedImageEntries.length) {
            closeAdminEventCollectionEditModal();
            return;
        }

        if (!adminEventCollectionUpdateEndpoint) {
            setAdminEventCollectionEditFeedback("Collection update endpoint is unavailable.", "error");
            return;
        }

        setAdminEventCollectionEditFeedback("", "error");
        setAdminEventCollectionEditSavingState(true);
        var pendingUploadCount = addedImageEntries.length;
        var completedUploadCount = 0;
        var addedPathByTempId = Object.create(null);
        var addedMediaTypeByTempId = Object.create(null);
        var currentCollectionFolder = collectionFolder;
        var finalCategoryLabel = nextCategoryLabel;
        var finalCollectionName = nextCollectionName;
        var anyRenameDetected = false;
        var uploadBatchSize = 1;
        var uploadBatches = [];

        if (pendingUploadCount > 0) {
            for (var batchIndex = 0; batchIndex < addedImageEntries.length; batchIndex += uploadBatchSize) {
                uploadBatches.push(addedImageEntries.slice(batchIndex, batchIndex + uploadBatchSize));
            }
        } else {
            uploadBatches.push([]);
        }

        function updateAdminCollectionUploadProgressLabel() {
            if (!adminEventCollectionEditSave || pendingUploadCount <= 0) {
                return;
            }

            adminEventCollectionEditSave.textContent = "Uploading " + String(completedUploadCount) + "/" + String(pendingUploadCount) + "...";
        }

        function buildAdminCollectionAddedImagePayload(batchEntries) {
            var entries = Array.isArray(batchEntries) ? batchEntries : [];

            if (!entries.length) {
                return Promise.resolve([]);
            }

            return Promise.all(entries.map(function (entry) {
                if (entry && typeof entry === "object" && entry.file instanceof File) {
                    return readAdminEventCollectionFileAsDataUrl(entry.file).then(function (dataUrl) {
                        return {
                            tempId: String(entry.tempId || ""),
                            dataUrl: String(dataUrl || ""),
                            fileName: String(entry.fileName || (entry.file && entry.file.name) || "")
                        };
                    });
                }

                var fallbackDataUrl = String(entry && entry.dataUrl ? entry.dataUrl : "");
                var isImagePayload = fallbackDataUrl.indexOf("data:image/") === 0;
                var isVideoPayload = fallbackDataUrl.indexOf("data:video/") === 0;

                if ((!isImagePayload && !isVideoPayload) || (isVideoPayload && !adminEventCollectionAllowVideo)) {
                    return Promise.reject(new Error("One of the selected media files could not be prepared for upload."));
                }

                return Promise.resolve({
                    tempId: String(entry && entry.tempId ? entry.tempId : ""),
                    dataUrl: fallbackDataUrl,
                    fileName: String(entry && entry.fileName ? entry.fileName : "")
                });
            }));
        }

        function submitAdminCollectionBatch(batchEntries, includeMetadata) {
            return buildAdminCollectionAddedImagePayload(batchEntries).then(function (addedPayload) {
                return fetch(adminEventCollectionUpdateEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        packageKey: packageKey,
                        collectionFolder: currentCollectionFolder,
                        categoryLabel: nextCategoryLabel,
                        collectionLabel: nextCollectionName,
                        excludedImagePaths: includeMetadata ? excludedImagePaths : [],
                        restoreImagePaths: includeMetadata ? restoreImagePaths : [],
                        addedImages: addedPayload
                    })
                })
                    .then(parseAdminJsonResponse)
                    .then(function (result) {
                        if (!result.ok || !result.payload || !result.payload.ok) {
                            var message = buildAdminJsonResponseError(result, "Unable to save collection changes.");
                            throw new Error(message);
                        }

                        finalCategoryLabel = String(result.payload.categoryLabel || finalCategoryLabel || nextCategoryLabel).trim() || nextCategoryLabel;
                        finalCollectionName = String(result.payload.collectionLabel || finalCollectionName || nextCollectionName).trim() || nextCollectionName;

                        var returnedCollectionFolder = normalizeAdminEventCollectionImagePath(result.payload.collectionFolder || currentCollectionFolder);
                        if (returnedCollectionFolder) {
                            currentCollectionFolder = returnedCollectionFolder;
                        }

                        if (Boolean(result.payload.renamed)) {
                            anyRenameDetected = true;
                        }

                        var returnedAddedImages = Array.isArray(result.payload.addedImages) ? result.payload.addedImages : [];

                        returnedAddedImages.forEach(function (entry) {
                            var tempId = String(entry && entry.tempId ? entry.tempId : "").trim();
                            var imagePath = normalizeAdminEventCollectionImagePath(entry && entry.imagePath ? entry.imagePath : "");
                            var mediaType = String(entry && entry.mediaType ? entry.mediaType : "").toLowerCase().trim();

                            if (!tempId || !imagePath) {
                                return;
                            }

                            addedPathByTempId[tempId] = imagePath;

                            if (mediaType === "video" || mediaType === "image") {
                                addedMediaTypeByTempId[tempId] = mediaType;
                            }
                        });

                        completedUploadCount += Array.isArray(batchEntries) ? batchEntries.length : 0;
                        updateAdminCollectionUploadProgressLabel();
                    });
            });
        }

        var batchChain = Promise.resolve();

        uploadBatches.forEach(function (batchEntries, index) {
            batchChain = batchChain.then(function () {
                return submitAdminCollectionBatch(batchEntries, index === 0);
            });
        });

        batchChain
            .then(function () {
                var returnedCategoryLabel = finalCategoryLabel || nextCategoryLabel;
                var returnedCollectionName = finalCollectionName || nextCollectionName;
                var returnedCollectionFolder = normalizeAdminEventCollectionImagePath(currentCollectionFolder || collectionFolder);

                adminEventCollectionEditState.categoryLabel = returnedCategoryLabel;
                adminEventCollectionEditState.collectionName = returnedCollectionName;

                if (returnedCollectionFolder) {
                    adminEventCollectionEditState.collectionFolder = returnedCollectionFolder;
                }

                card.setAttribute("data-admin-event-collection-category", returnedCategoryLabel);
                card.setAttribute("data-admin-event-collection-name", returnedCollectionName);

                if (returnedCollectionFolder) {
                    card.setAttribute("data-admin-event-collection-folder", returnedCollectionFolder);

                    if (adminEventCollectionEditCollectionFolderInput) {
                        adminEventCollectionEditCollectionFolderInput.value = returnedCollectionFolder;
                    }
                }

                var cardTitle = card.querySelector(".event-gallery-meta h3");
                if (cardTitle) {
                    cardTitle.textContent = returnedCollectionName;
                }

                var editButton = card.querySelector("[data-admin-event-collection-edit]");
                if (editButton) {
                    editButton.setAttribute("aria-label", "Edit collection " + returnedCollectionName);
                }

                var removeButton = card.querySelector("[data-admin-remove-event-collection]");
                if (removeButton) {
                    removeButton.setAttribute("aria-label", "Archive collection " + returnedCollectionName);
                }

                if (adminEventCollectionEditTitle) {
                    adminEventCollectionEditTitle.textContent = "Edit Collection: " + returnedCollectionName;
                }

                if (anyRenameDetected || returnedCategoryLabel !== categoryLabel) {
                    closeAdminEventCollectionEditModal(true);
                    window.location.reload();
                    return;
                }

                if (pendingUploadCount > 0 && Object.keys(addedPathByTempId).length < pendingUploadCount) {
                    throw new Error("Some newly added images were not saved. Please try again.");
                }

                var archivedImageMap = Object.create(null);
                var archivedImagesRaw = String(card.getAttribute("data-admin-event-collection-archived-images") || "[]");

                try {
                    var decodedArchivedImages = JSON.parse(archivedImagesRaw);

                    if (Array.isArray(decodedArchivedImages)) {
                        decodedArchivedImages.forEach(function (path) {
                            var normalizedArchivedPath = normalizeAdminEventCollectionImagePath(path);

                            if (!normalizedArchivedPath) {
                                return;
                            }

                            archivedImageMap[normalizedArchivedPath.toLowerCase()] = normalizedArchivedPath;
                        });
                    }
                } catch (error) {
                    archivedImageMap = Object.create(null);
                }

                restoreImagePaths.forEach(function (path) {
                    var normalizedRestorePath = normalizeAdminEventCollectionImagePath(path);

                    if (!normalizedRestorePath) {
                        return;
                    }

                    delete archivedImageMap[normalizedRestorePath.toLowerCase()];
                });

                excludedImagePaths.forEach(function (path) {
                    var normalizedExcludedPath = normalizeAdminEventCollectionImagePath(path);

                    if (!normalizedExcludedPath) {
                        return;
                    }

                    archivedImageMap[normalizedExcludedPath.toLowerCase()] = normalizedExcludedPath;
                });

                var nextArchivedImages = Object.keys(archivedImageMap).map(function (key) {
                    return archivedImageMap[key];
                });

                card.setAttribute("data-admin-event-collection-archived-images", JSON.stringify(nextArchivedImages));

                adminEventCollectionEditState.images = adminEventCollectionEditState.images.filter(function (imageEntry) {
                    if (imageEntry.isExcluded) {
                        revokeAdminEventCollectionObjectUrl(imageEntry);
                        return false;
                    }

                    if (imageEntry.isNew) {
                        var resolvedPath = normalizeAdminEventCollectionImagePath(addedPathByTempId[String(imageEntry.tempId || "")]);
                        if (!resolvedPath) {
                            revokeAdminEventCollectionObjectUrl(imageEntry);
                            return false;
                        }

                        revokeAdminEventCollectionObjectUrl(imageEntry);
                        imageEntry.imagePath = resolvedPath;
                        imageEntry.previewUrl = buildAdminEventCollectionImageUrl(resolvedPath);
                        imageEntry.mediaType = String(addedMediaTypeByTempId[String(imageEntry.tempId || "")] || resolveAdminEventCollectionMediaTypeFromPath(resolvedPath));
                        imageEntry.file = null;
                        imageEntry.dataUrl = "";
                        imageEntry.isObjectUrl = false;
                        imageEntry.isNew = false;
                        imageEntry.isArchived = false;
                    } else if (imageEntry.isArchived) {
                        imageEntry.isArchived = false;
                        imageEntry.previewUrl = buildAdminEventCollectionImageUrl(imageEntry.imagePath || imageEntry.previewUrl || "");
                        imageEntry.mediaType = resolveAdminEventCollectionMediaTypeFromPath(imageEntry.imagePath || imageEntry.previewUrl || "");
                    }

                    return true;
                });

                applyAdminEventCollectionImagesToCard(card, adminEventCollectionEditState.images);
                closeAdminEventCollectionEditModal(true);
            })
            .catch(function (error) {
                setAdminEventCollectionEditFeedback(error.message || "Unable to save collection changes.", "error");
            })
            .finally(function () {
                setAdminEventCollectionEditSavingState(false);
            });
    }

    if (adminEventCollectionCreateOpenButton) {
        adminEventCollectionCreateOpenButton.addEventListener("click", function () {
            if (adminEventCollectionCreateState.isSaving) {
                return;
            }

            openAdminEventCollectionCreateModal();
        });
    }

    if (adminEventCollectionCreateClose) {
        adminEventCollectionCreateClose.addEventListener("click", function () {
            closeAdminEventCollectionCreateModal();
        });
    }

    if (adminEventCollectionCreateCancel) {
        adminEventCollectionCreateCancel.addEventListener("click", function () {
            closeAdminEventCollectionCreateModal();
        });
    }

    if (adminEventCollectionCreateBackdrop) {
        adminEventCollectionCreateBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEventCollectionCreateBackdrop) {
                closeAdminEventCollectionCreateModal();
            }
        });
    }

    if (adminEventCollectionCreateForm) {
        adminEventCollectionCreateForm.addEventListener("submit", function (event) {
            event.preventDefault();
            submitAdminEventCollectionCreate();
        });
    }

    adminEventCollectionEditButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest("[data-admin-event-collection-card]");
            openAdminEventCollectionEditModal(card);
        });
    });

    if (adminEventCollectionEditGrid) {
        adminEventCollectionEditGrid.addEventListener("click", function (event) {
            var toggleButton = event.target.closest("[data-admin-event-collection-toggle]");

            if (!toggleButton || adminEventCollectionEditState.isSaving) {
                return;
            }

            var imageIndex = Number.parseInt(String(toggleButton.getAttribute("data-admin-event-collection-toggle") || "-1"), 10);
            if (!Number.isFinite(imageIndex) || imageIndex < 0 || imageIndex >= adminEventCollectionEditState.images.length) {
                return;
            }

            adminEventCollectionEditState.images[imageIndex].isExcluded = !adminEventCollectionEditState.images[imageIndex].isExcluded;
            renderAdminEventCollectionEditGrid();
        });
    }

    if (adminEventCollectionAddTrigger) {
        adminEventCollectionAddTrigger.addEventListener("click", function () {
            if (adminEventCollectionEditState.isSaving || !adminEventCollectionAddInput) {
                return;
            }

            adminEventCollectionAddInput.click();
        });
    }

    if (adminEventCollectionAddInput) {
        adminEventCollectionAddInput.addEventListener("change", function () {
            if (adminEventCollectionEditState.isSaving) {
                return;
            }

            var selectedFiles = adminEventCollectionAddInput.files;
            adminEventCollectionAddInput.value = "";

            handleAdminEventCollectionAddFiles(selectedFiles);
        });
    }

    if (adminEventCollectionEditClose) {
        adminEventCollectionEditClose.addEventListener("click", function () {
            closeAdminEventCollectionEditModal();
        });
    }

    if (adminEventCollectionEditCancel) {
        adminEventCollectionEditCancel.addEventListener("click", function () {
            closeAdminEventCollectionEditModal();
        });
    }

    if (adminEventCollectionEditBackdrop) {
        adminEventCollectionEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEventCollectionEditBackdrop) {
                closeAdminEventCollectionEditModal();
            }
        });
    }

    if (adminEventCollectionEditForm) {
        adminEventCollectionEditForm.addEventListener("submit", function (event) {
            event.preventDefault();
            submitAdminEventCollectionEditChanges();
        });
    }

    adminEventCollectionRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest("[data-admin-event-collection-card]");

            if (!card || !adminEventCollectionArchiveEndpoint || button.disabled || card.classList.contains("is-admin-removing")) {
                return;
            }

            var collectionName = String(card.getAttribute("data-admin-event-collection-name") || "This collection").trim();

            openAdminActionModal({
                title: "Archive Collection",
                message: collectionName + " will be moved to archive and can be restored from the archive page or Undo.",
                confirmLabel: "Archive Collection",
                onConfirm: function () {
                    archiveEventCollection(card, button);
                }
            });
        });
    });

    if (adminAddCard && adminEditBackdrop) {
        adminAddCard.addEventListener("click", function () {
            var createEndpoint = adminEditBackdrop.getAttribute("data-admin-create-endpoint") || "";
            var productBaseUrl = adminEditBackdrop.getAttribute("data-admin-product-base-url") || "";

            if (!createEndpoint || !productBaseUrl) {
                return;
            }

            if (adminAddCard.classList.contains("is-admin-creating")) {
                return;
            }

            adminAddCard.classList.add("is-admin-creating");

            fetch(createEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({})
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok || !result.payload.newKey) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to create product.";
                        throw new Error(message);
                    }

                    window.location.href = productBaseUrl + encodeURIComponent(String(result.payload.newKey));
                })
                .catch(function (error) {
                    adminAddCard.classList.remove("is-admin-creating");
                    window.alert(error.message || "Unable to create product.");
                });
        });
    }

    productCards.forEach(function (card) {
        ensureCardBrandNameData(card);
    });

    function parseMoneyValue(value) {
        var normalized = String(value || "").replace(/[^0-9.]/g, "");
        var parsed = Number.parseFloat(normalized);

        if (!Number.isFinite(parsed)) {
            return 0;
        }

        return parsed;
    }

    function formatPeso(value) {
        return "\u20B1 " + Number(value).toFixed(2);
    }

    function normalizeBrandValue(value) {
        var normalized = String(value || "").toLowerCase().trim();

        if (!normalized || normalized === manageBrandsOptionValue) {
            return defaultBrandValue;
        }

        if (Object.prototype.hasOwnProperty.call(brandValueToLabel, normalized)) {
            return normalized;
        }

        return normalized;
    }

    function getBrandLabel(value) {
        var normalized = normalizeBrandValue(value);

        if (Object.prototype.hasOwnProperty.call(brandValueToLabel, normalized)) {
            return brandValueToLabel[normalized];
        }

        var fallback = formatBrandLabelFromValue(normalized);
        if (fallback) {
            return fallback;
        }

        return brandValueToLabel[defaultBrandValue] || "Brand";
    }

    function splitProductDisplayName(brandValue, fullName) {
        var normalizedBrand = normalizeBrandValue(brandValue);
        var brandLabel = getBrandLabel(normalizedBrand);
        var cleanName = String(fullName || "").trim();

        if (!cleanName) {
            return "";
        }

        var escapedBrand = brandLabel.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        var prefixRegex = new RegExp("^" + escapedBrand + "\\s+", "i");

        return cleanName.replace(prefixRegex, "").trim();
    }

    function composeProductDisplayName(brandValue, productName) {
        var brandLabel = getBrandLabel(brandValue);
        var cleanName = String(productName || "").trim();

        if (!cleanName) {
            return brandLabel;
        }

        return brandLabel + " " + cleanName;
    }

    function ensureCardBrandNameData(card) {
        if (!card) {
            return;
        }

        var currentBrand = normalizeBrandValue(card.getAttribute("data-brand"));
        var storedName = String(card.getAttribute("data-product-name") || "").trim();
        var titleLink = card.querySelector(".product-title-link");
        var fallbackTitle = titleLink ? titleLink.textContent.trim() : "";

        if (!storedName) {
            storedName = splitProductDisplayName(currentBrand, fallbackTitle);
        }

        card.setAttribute("data-brand", currentBrand);
        card.setAttribute("data-product-name", storedName);
    }

    function linesToTextarea(value) {
        if (!Array.isArray(value)) {
            return "";
        }

        return value
            .map(function (line) {
                return String(line || "").trim();
            })
            .filter(function (line) {
                return line !== "";
            })
            .join("\n");
    }

    function textareaToLines(value) {
        return String(value || "")
            .split(/\r\n|\r|\n/)
            .map(function (line) {
                return line.trim();
            })
            .filter(function (line) {
                return line !== "";
            });
    }

    function getActiveAdminProductData() {
        if (!activeAdminEditCard) {
            return null;
        }

        var productKey = activeAdminEditCard.getAttribute("data-product-key") || "";

        if (!productKey || !adminProducts || typeof adminProducts !== "object") {
            return null;
        }

        return adminProducts[productKey] || null;
    }

    function syncAdminPreviewTransform() {
        if (!adminEditPreviewWrap || !adminEditPreviewImage || !adminEditZoom) {
            return;
        }

        var bounds = getCoverRenderBounds(adminEditPreviewWrap, adminEditPreviewImage, adminCropState.zoom);
        var coverScale = adminEditPreviewImage.naturalWidth > 0
            ? (bounds.renderedWidth / adminEditPreviewImage.naturalWidth)
            : 1;

        adminEditPreviewWrap.style.setProperty("--admin-crop-zoom", String(adminCropState.zoom));
        adminEditPreviewWrap.style.setProperty("--admin-crop-x", String(adminCropState.offsetX) + "px");
        adminEditPreviewWrap.style.setProperty("--admin-crop-y", String(adminCropState.offsetY) + "px");
        adminEditPreviewWrap.style.setProperty("--admin-crop-scale", String(coverScale));
        adminEditPreviewWrap.classList.toggle("is-crop-active", adminCropState.isCropping);
    }

    function setAdminCropWorkspaceVisible(isVisible) {
        adminCropState.isCropping = isVisible;

        if (adminCropWorkspace) {
            adminCropWorkspace.hidden = !isVisible;
        }

        if (adminEditImageActions) {
            adminEditImageActions.hidden = isVisible;

            Array.prototype.forEach.call(adminEditImageActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        if (adminEditMainActions) {
            adminEditMainActions.hidden = isVisible;

            Array.prototype.forEach.call(adminEditMainActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        syncAdminPreviewTransform();
    }

    function getCoverRenderBounds(previewWrap, previewImage, zoomValue) {
        if (!previewWrap || !previewImage || !previewImage.naturalWidth || !previewImage.naturalHeight) {
            return {
                rectWidth: 0,
                rectHeight: 0,
                renderedWidth: 0,
                renderedHeight: 0,
                maxShiftX: 0,
                maxShiftY: 0
            };
        }

        var rect = previewWrap.getBoundingClientRect();
        var zoom = Math.max(1, Number(zoomValue || 1));
        var baseScale = Math.max(rect.width / previewImage.naturalWidth, rect.height / previewImage.naturalHeight);
        var renderedWidth = previewImage.naturalWidth * baseScale * zoom;
        var renderedHeight = previewImage.naturalHeight * baseScale * zoom;

        return {
            rectWidth: rect.width,
            rectHeight: rect.height,
            renderedWidth: renderedWidth,
            renderedHeight: renderedHeight,
            maxShiftX: Math.max(0, (renderedWidth - rect.width) / 2),
            maxShiftY: Math.max(0, (renderedHeight - rect.height) / 2)
        };
    }

    function clampAdminCropOffsets(nextX, nextY) {
        if (!adminEditPreviewWrap || !adminEditPreviewImage) {
            return { x: nextX, y: nextY };
        }

        var bounds = getCoverRenderBounds(adminEditPreviewWrap, adminEditPreviewImage, adminCropState.zoom);
        var clampedX = Math.min(bounds.maxShiftX, Math.max(-bounds.maxShiftX, nextX));
        var clampedY = Math.min(bounds.maxShiftY, Math.max(-bounds.maxShiftY, nextY));

        return {
            x: clampedX,
            y: clampedY
        };
    }

    function resetAdminCropState() {
        adminCropState.zoom = 1;
        adminCropState.offsetX = 0;
        adminCropState.offsetY = 0;
        adminCropState.isDragging = false;
        adminCropState.dragPointerId = null;

        if (adminEditZoom) {
            adminEditZoom.value = "1";
        }

        syncAdminPreviewTransform();
    }

    function buildAdminCropDataUrlFromPreview() {
        if (!adminEditPreviewImage || !adminEditPreviewImage.src || !adminEditPreviewImage.naturalWidth || !adminEditPreviewImage.naturalHeight) {
            return null;
        }

        var outputWidth = 900;
        var outputHeight = Math.round(outputWidth * (adminCoverAspect.height / adminCoverAspect.width));
        var canvas = document.createElement("canvas");
        canvas.width = outputWidth;
        canvas.height = outputHeight;

        var ctx = canvas.getContext("2d");
        if (!ctx) {
            return null;
        }

        var zoomValue = Math.max(1, Number(adminCropState.zoom || 1));
        var bounds = getCoverRenderBounds(adminEditPreviewWrap, adminEditPreviewImage, zoomValue);
        var offsetScaleX = bounds.rectWidth > 0 ? (outputWidth / bounds.rectWidth) : 1;
        var offsetScaleY = bounds.rectHeight > 0 ? (outputHeight / bounds.rectHeight) : 1;
        var scaleToCover = Math.max(outputWidth / adminEditPreviewImage.naturalWidth, outputHeight / adminEditPreviewImage.naturalHeight);
        var scale = scaleToCover * zoomValue;
        var drawWidth = adminEditPreviewImage.naturalWidth * scale;
        var drawHeight = adminEditPreviewImage.naturalHeight * scale;
        var drawX = ((outputWidth - drawWidth) / 2) + (adminCropState.offsetX * offsetScaleX);
        var drawY = ((outputHeight - drawHeight) / 2) + (adminCropState.offsetY * offsetScaleY);

        ctx.clearRect(0, 0, outputWidth, outputHeight);
        ctx.drawImage(adminEditPreviewImage, drawX, drawY, drawWidth, drawHeight);

        return canvas.toDataURL("image/png");
    }

    function clampDiscount(value) {
        var parsed = Number.parseInt(String(value || "0"), 10);

        if (!Number.isFinite(parsed)) {
            return 0;
        }

        return Math.min(95, Math.max(0, parsed));
    }

    function normalizeServicePackageDurationUnit(value) {
        var normalized = String(value || "").trim().toLowerCase();

        if (normalized === "day" || normalized === "days") {
            return "days";
        }

        return "hours";
    }

    function clampServicePackageDurationValue(unit, value) {
        var normalizedUnit = normalizeServicePackageDurationUnit(unit);
        var parsed = Number.parseInt(String(value || ""), 10);

        if (!Number.isFinite(parsed) || parsed < 1) {
            parsed = 1;
        }

        if (normalizedUnit === "days") {
            return Math.max(1, Math.min(14, parsed));
        }

        return Math.max(1, Math.min(24, parsed));
    }

    function syncAdminServicePackageDurationConstraints() {
        if (!adminServiceEditDurationUnit || !adminServiceEditDurationValue) {
            return;
        }

        var unit = normalizeServicePackageDurationUnit(adminServiceEditDurationUnit.value);
        var maxValue = unit === "days" ? 14 : 24;

        adminServiceEditDurationValue.min = "1";
        adminServiceEditDurationValue.max = String(maxValue);
        adminServiceEditDurationValue.step = "1";

        var clampedValue = clampServicePackageDurationValue(unit, adminServiceEditDurationValue.value);
        adminServiceEditDurationValue.value = String(clampedValue);
    }

    function setAdminServiceEditTab(tabKey) {
        var normalizedTabRaw = String(tabKey || "details").toLowerCase();
        var normalizedTab = normalizedTabRaw === "camera" || normalizedTabRaw === "duration"
            ? normalizedTabRaw
            : "details";

        adminServiceEditTabButtons.forEach(function (button) {
            var buttonTab = String(button.getAttribute("data-admin-service-edit-tab") || "details").toLowerCase();
            var isActive = buttonTab === normalizedTab;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-selected", isActive ? "true" : "false");
        });

        adminServiceEditPanels.forEach(function (panel) {
            var panelTab = String(panel.getAttribute("data-admin-service-edit-panel") || "details").toLowerCase();
            var isActive = panelTab === normalizedTab;
            panel.classList.toggle("is-active", isActive);
            panel.hidden = !isActive;
        });
    }

    function closeAdminServiceEditModal() {
        if (!adminServiceEditBackdrop) {
            return;
        }

        adminServiceEditBackdrop.hidden = true;
        activeAdminServicePackageCard = null;
        setAdminServiceEditTab("details");
        syncAdminModalBodyLock();
    }

    function openAdminServiceEditModal(card) {
        if (!adminServiceEditBackdrop || !adminServiceEditForm || !card) {
            return;
        }

        activeAdminServicePackageCard = card;

        var packageKey = String(card.getAttribute("data-admin-service-package-key") || "").trim();
        var packageTitle = String(card.getAttribute("data-admin-service-package-title") || "").trim();
        var packageDescription = String(card.getAttribute("data-admin-service-package-description") || "").trim();
        var packagePrice = Number.parseFloat(String(card.getAttribute("data-admin-service-package-price") || "0"));
        var packageDiscount = clampDiscount(card.getAttribute("data-admin-service-package-discount") || "0");
        var packageDurationUnit = normalizeServicePackageDurationUnit(card.getAttribute("data-admin-service-package-duration-unit") || "hours");
        var packageDurationValue = Number.parseInt(String(card.getAttribute("data-admin-service-package-duration-value") || ""), 10);
        var packageDurationMin = Number.parseInt(String(card.getAttribute("data-admin-service-package-duration-min") || "1"), 10);
        var packageDurationMax = Number.parseInt(String(card.getAttribute("data-admin-service-package-duration-max") || "1"), 10);
        var packageCamera1 = String(card.getAttribute("data-admin-service-package-camera-1") || "").trim();
        var packageCamera2 = String(card.getAttribute("data-admin-service-package-camera-2") || "").trim();
        var packageBackupCamera1 = String(card.getAttribute("data-admin-service-package-backup-camera-1") || "").trim();
        var packageBackupCamera2 = String(card.getAttribute("data-admin-service-package-backup-camera-2") || "").trim();

        if (!Number.isFinite(packageDurationValue) || packageDurationValue < 1) {
            if (!Number.isFinite(packageDurationMin) || packageDurationMin < 1) {
                packageDurationMin = 1;
            }

            if (!Number.isFinite(packageDurationMax) || packageDurationMax < packageDurationMin) {
                packageDurationMax = packageDurationMin;
            }

            packageDurationUnit = "hours";
            packageDurationValue = packageDurationMax;
        }

        packageDurationUnit = normalizeServicePackageDurationUnit(packageDurationUnit);
        packageDurationValue = clampServicePackageDurationValue(packageDurationUnit, packageDurationValue);

        if (adminServiceEditKey) {
            adminServiceEditKey.value = packageKey;
        }

        if (adminServiceEditName) {
            adminServiceEditName.value = packageTitle;
        }

        if (adminServiceEditDescription) {
            adminServiceEditDescription.value = packageDescription;
        }

        if (adminServiceEditPrice) {
            adminServiceEditPrice.value = Number.isFinite(packagePrice) ? packagePrice.toFixed(2) : "0.00";
        }

        if (adminServiceEditDiscount) {
            adminServiceEditDiscount.value = String(packageDiscount);
        }

        if (adminServiceEditDurationUnit) {
            adminServiceEditDurationUnit.value = packageDurationUnit;
        }

        if (adminServiceEditDurationValue) {
            adminServiceEditDurationValue.value = String(packageDurationValue);
        }

        if (adminServiceEditCamera1) {
            adminServiceEditCamera1.value = packageCamera1;
        }

        if (adminServiceEditCamera2) {
            adminServiceEditCamera2.value = packageCamera2;
        }

        if (adminServiceEditBackupCamera1) {
            adminServiceEditBackupCamera1.value = packageBackupCamera1;
        }

        if (adminServiceEditBackupCamera2) {
            adminServiceEditBackupCamera2.value = packageBackupCamera2;
        }

        setAdminServiceEditTab("details");
        adminServiceEditBackdrop.hidden = false;
        syncAdminServicePackageDurationConstraints();
        syncAdminModalBodyLock();
    }

    if (adminServiceEditDurationUnit) {
        adminServiceEditDurationUnit.addEventListener("change", syncAdminServicePackageDurationConstraints);
    }

    adminServiceEditButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest("[data-admin-service-package]");
            openAdminServiceEditModal(card);
        });
    });

    adminServiceEditTabButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var tabKey = String(button.getAttribute("data-admin-service-edit-tab") || "details");
            setAdminServiceEditTab(tabKey);
        });
    });

    if (adminServiceEditCancel) {
        adminServiceEditCancel.addEventListener("click", closeAdminServiceEditModal);
    }

    if (adminServiceEditClose) {
        adminServiceEditClose.addEventListener("click", closeAdminServiceEditModal);
    }

    if (adminServiceEditBackdrop) {
        adminServiceEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminServiceEditBackdrop) {
                closeAdminServiceEditModal();
            }
        });
    }

    function closeAdminEventEditModal() {
        if (!adminEventEditBackdrop) {
            return;
        }

        adminEventEditBackdrop.hidden = true;
        activeAdminEventPackageCard = null;
        syncAdminModalBodyLock();
    }

    function openAdminEventEditModal(card) {
        if (!adminEventEditBackdrop || !adminEventEditForm || !card) {
            return;
        }

        activeAdminEventPackageCard = card;

        var packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        var packageTitle = String(card.getAttribute("data-admin-event-package-title") || "").trim();
        var packagePrice = Number.parseFloat(String(card.getAttribute("data-admin-event-package-price") || "0"));
        var packageDiscount = clampDiscount(card.getAttribute("data-admin-event-package-discount") || "0");
        var packageCamera1 = String(card.getAttribute("data-admin-event-package-camera-1") || "").trim();
        var packageCamera2 = String(card.getAttribute("data-admin-event-package-camera-2") || "").trim();
        var packageBackupCamera1 = String(card.getAttribute("data-admin-event-package-backup-camera-1") || "").trim();
        var packageBackupCamera2 = String(card.getAttribute("data-admin-event-package-backup-camera-2") || "").trim();

        if (adminEventEditKey) {
            adminEventEditKey.value = packageKey;
        }

        if (adminEventEditName) {
            adminEventEditName.value = packageTitle;
        }

        if (adminEventEditPrice) {
            adminEventEditPrice.value = Number.isFinite(packagePrice) ? packagePrice.toFixed(2) : "0.00";
        }

        if (adminEventEditDiscount) {
            adminEventEditDiscount.value = String(packageDiscount);
        }

        if (adminEventEditCamera1) {
            adminEventEditCamera1.value = packageCamera1;
        }

        if (adminEventEditCamera2) {
            adminEventEditCamera2.value = packageCamera2;
        }

        if (adminEventEditBackupCamera1) {
            adminEventEditBackupCamera1.value = packageBackupCamera1;
        }

        if (adminEventEditBackupCamera2) {
            adminEventEditBackupCamera2.value = packageBackupCamera2;
        }

        adminEventEditBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    adminEventEditButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest("[data-admin-event-package]");
            openAdminEventEditModal(card);
        });
    });

    if (adminEventEditCancel) {
        adminEventEditCancel.addEventListener("click", closeAdminEventEditModal);
    }

    if (adminEventEditClose) {
        adminEventEditClose.addEventListener("click", closeAdminEventEditModal);
    }

    if (adminEventEditBackdrop) {
        adminEventEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEventEditBackdrop) {
                closeAdminEventEditModal();
            }
        });
    }

    function parseAdminEventThumbSelection(rawValue) {
        if (typeof rawValue !== "string" || rawValue.trim() === "") {
            return [];
        }

        try {
            var parsed = JSON.parse(rawValue);

            if (!Array.isArray(parsed)) {
                return [];
            }

            var unique = [];
            var seen = Object.create(null);

            parsed.forEach(function (entry) {
                var path = String(entry || "").trim();

                if (!path || seen[path]) {
                    return;
                }

                seen[path] = true;
                unique.push(path);
            });

            return unique;
        } catch (error) {
            return [];
        }
    }

    function getAdminEventImageFolder(imagePath) {
        var normalizedPath = String(imagePath || "").replace(/\\/g, "/").replace(/^\/+/, "");
        var prefix = "assets/event_packages/";

        if (normalizedPath.indexOf(prefix) !== 0) {
            return "";
        }

        var remainder = normalizedPath.slice(prefix.length);
        var slashIndex = remainder.indexOf("/");

        return (slashIndex >= 0 ? remainder.slice(0, slashIndex) : remainder).trim();
    }

    function syncAdminEventThumbSelectionUI() {
        if (!adminEventThumbItems.length) {
            if (adminEventThumbsInput) {
                adminEventThumbsInput.value = JSON.stringify(adminEventThumbSelection);
            }

            return;
        }

        var visibleCount = 0;

        adminEventThumbItems.forEach(function (item) {
            var path = String(item.getAttribute("data-image-path") || "");
            var itemFolder = String(item.getAttribute("data-image-folder") || "").trim();
            var isVisible = activeAdminEventThumbFolder === "" || itemFolder === activeAdminEventThumbFolder;
            var orderBadge = item.querySelector("[data-admin-event-thumb-order]");
            var selectedIndex = adminEventThumbSelection.indexOf(path);
            var isSelected = selectedIndex >= 0;

            item.hidden = !isVisible;

            if (!isVisible) {
                item.classList.remove("is-selected");
                if (orderBadge) {
                    orderBadge.hidden = true;
                    orderBadge.textContent = "";
                }

                return;
            }

            visibleCount += 1;

            item.classList.toggle("is-selected", isSelected);

            if (orderBadge) {
                if (isSelected) {
                    orderBadge.hidden = false;
                    orderBadge.textContent = String(selectedIndex + 1);
                } else {
                    orderBadge.hidden = true;
                    orderBadge.textContent = "";
                }
            }
        });

        if (adminEventThumbsFolderEmpty) {
            adminEventThumbsFolderEmpty.hidden = visibleCount > 0;
        }

        if (adminEventThumbsInput) {
            adminEventThumbsInput.value = JSON.stringify(adminEventThumbSelection);
        }
    }

    function closeAdminEventThumbsModal() {
        if (!adminEventThumbsBackdrop) {
            return;
        }

        adminEventThumbsBackdrop.hidden = true;
        activeAdminEventThumbsCard = null;
        activeAdminEventThumbFolder = "";
        adminEventThumbSelection = [];
        syncAdminModalBodyLock();
    }

    function openAdminEventThumbsModal(card, folderOverride) {
        if (!adminEventThumbsBackdrop || !card) {
            return;
        }

        activeAdminEventThumbsCard = card;

        var packageKey = String(card.getAttribute("data-admin-event-package-key") || "").trim();
        var packageTitle = String(card.getAttribute("data-admin-event-package-title") || "").trim();
        var packageFolder = String(folderOverride || card.getAttribute("data-admin-event-package-folder") || "").trim();
        var selectedRaw = String(card.getAttribute("data-admin-event-selected-thumbnails") || "[]");

        activeAdminEventThumbFolder = packageFolder;
        adminEventThumbSelection = parseAdminEventThumbSelection(selectedRaw).filter(function (path) {
            return getAdminEventImageFolder(path) === activeAdminEventThumbFolder;
        });

        if (adminEventThumbsKey) {
            adminEventThumbsKey.value = packageKey;
        }

        if (adminEventThumbsPackageTitle) {
            adminEventThumbsPackageTitle.textContent = packageTitle || "this package";
        }

        syncAdminEventThumbSelectionUI();

        adminEventThumbsBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    if (adminEventSetThumbButtonInEdit) {
        adminEventSetThumbButtonInEdit.addEventListener("click", function () {
            if (!activeAdminEventPackageCard) {
                return;
            }

            openAdminEventThumbsModal(activeAdminEventPackageCard);
        });
    }

    adminEventThumbItems.forEach(function (item) {
        item.addEventListener("click", function () {
            var path = String(item.getAttribute("data-image-path") || "").trim();

            if (!path) {
                return;
            }

            var selectedIndex = adminEventThumbSelection.indexOf(path);

            if (selectedIndex >= 0) {
                adminEventThumbSelection.splice(selectedIndex, 1);
            } else {
                adminEventThumbSelection.push(path);
            }

            syncAdminEventThumbSelectionUI();
        });
    });

    if (adminEventThumbsClose) {
        adminEventThumbsClose.addEventListener("click", closeAdminEventThumbsModal);
    }

    if (adminEventThumbsCancel) {
        adminEventThumbsCancel.addEventListener("click", closeAdminEventThumbsModal);
    }

    if (adminEventThumbsBackdrop) {
        adminEventThumbsBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEventThumbsBackdrop) {
                closeAdminEventThumbsModal();
            }
        });
    }

    if (adminEventThumbsForm) {
        adminEventThumbsForm.addEventListener("submit", function () {
            syncAdminEventThumbSelectionUI();
        });
    }

    function parseAdminServiceThumbSelection(rawValue) {
        if (typeof rawValue !== "string" || rawValue.trim() === "") {
            return [];
        }

        try {
            var parsed = JSON.parse(rawValue);

            if (!Array.isArray(parsed)) {
                return [];
            }

            var unique = [];
            var seen = Object.create(null);

            parsed.forEach(function (entry) {
                var path = String(entry || "").trim();

                if (!path || seen[path]) {
                    return;
                }

                seen[path] = true;
                unique.push(path);
            });

            return unique;
        } catch (error) {
            return [];
        }
    }

    function getAdminServiceImageFolder(imagePath) {
        var normalizedPath = String(imagePath || "").replace(/\\/g, "/").replace(/^\/+/, "");
        var prefix = "assets/service_packages/";

        if (normalizedPath.indexOf(prefix) !== 0) {
            return "";
        }

        var remainder = normalizedPath.slice(prefix.length);
        var slashIndex = remainder.indexOf("/");

        return (slashIndex >= 0 ? remainder.slice(0, slashIndex) : remainder).trim();
    }

    function syncAdminServiceThumbSelectionUI() {
        if (!adminServiceThumbItems.length) {
            if (adminServiceThumbsInput) {
                adminServiceThumbsInput.value = JSON.stringify(adminServiceThumbSelection);
            }

            return;
        }

        var visibleCount = 0;

        adminServiceThumbItems.forEach(function (item) {
            var path = String(item.getAttribute("data-image-path") || "");
            var itemFolder = String(item.getAttribute("data-image-folder") || "").trim();
            var isVisible = activeAdminServiceThumbFolder === "" || itemFolder === activeAdminServiceThumbFolder;
            var orderBadge = item.querySelector("[data-admin-service-thumb-order]");
            var selectedIndex = adminServiceThumbSelection.indexOf(path);
            var isSelected = selectedIndex >= 0;

            item.hidden = !isVisible;

            if (!isVisible) {
                item.classList.remove("is-selected");
                if (orderBadge) {
                    orderBadge.hidden = true;
                    orderBadge.textContent = "";
                }

                return;
            }

            visibleCount += 1;

            item.classList.toggle("is-selected", isSelected);

            if (orderBadge) {
                if (isSelected) {
                    orderBadge.hidden = false;
                    orderBadge.textContent = String(selectedIndex + 1);
                } else {
                    orderBadge.hidden = true;
                    orderBadge.textContent = "";
                }
            }
        });

        if (adminServiceThumbsFolderEmpty) {
            adminServiceThumbsFolderEmpty.hidden = visibleCount > 0;
        }

        if (adminServiceThumbsInput) {
            adminServiceThumbsInput.value = JSON.stringify(adminServiceThumbSelection);
        }
    }

    function closeAdminServiceThumbsModal() {
        if (!adminServiceThumbsBackdrop) {
            return;
        }

        adminServiceThumbsBackdrop.hidden = true;
        activeAdminServiceThumbsCard = null;
        activeAdminServiceThumbFolder = "";
        adminServiceThumbSelection = [];
        syncAdminModalBodyLock();
    }

    function openAdminServiceThumbsModal(card, folderOverride) {
        if (!adminServiceThumbsBackdrop || !card) {
            return;
        }

        activeAdminServiceThumbsCard = card;

        var packageKey = String(card.getAttribute("data-admin-service-package-key") || "").trim();
        var packageTitle = String(card.getAttribute("data-admin-service-package-title") || "").trim();
        var packageFolder = String(folderOverride || card.getAttribute("data-admin-service-package-folder") || "").trim();
        var selectedRaw = String(card.getAttribute("data-admin-service-selected-thumbnails") || "[]");

        activeAdminServiceThumbFolder = packageFolder;
        adminServiceThumbSelection = parseAdminServiceThumbSelection(selectedRaw).filter(function (path) {
            return getAdminServiceImageFolder(path) === activeAdminServiceThumbFolder;
        });

        if (adminServiceThumbsKey) {
            adminServiceThumbsKey.value = packageKey;
        }

        if (adminServiceThumbsPackageTitle) {
            adminServiceThumbsPackageTitle.textContent = packageTitle || "this package";
        }

        syncAdminServiceThumbSelectionUI();

        adminServiceThumbsBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    if (adminServiceSetThumbButtonInEdit) {
        adminServiceSetThumbButtonInEdit.addEventListener("click", function () {
            if (!activeAdminServicePackageCard) {
                return;
            }

            openAdminServiceThumbsModal(activeAdminServicePackageCard);
        });
    }

    adminServiceThumbItems.forEach(function (item) {
        item.addEventListener("click", function () {
            var path = String(item.getAttribute("data-image-path") || "").trim();

            if (!path) {
                return;
            }

            var selectedIndex = adminServiceThumbSelection.indexOf(path);

            if (selectedIndex >= 0) {
                adminServiceThumbSelection.splice(selectedIndex, 1);
            } else {
                adminServiceThumbSelection.push(path);
            }

            syncAdminServiceThumbSelectionUI();
        });
    });

    if (adminServiceThumbsClose) {
        adminServiceThumbsClose.addEventListener("click", closeAdminServiceThumbsModal);
    }

    if (adminServiceThumbsCancel) {
        adminServiceThumbsCancel.addEventListener("click", closeAdminServiceThumbsModal);
    }

    if (adminServiceThumbsBackdrop) {
        adminServiceThumbsBackdrop.addEventListener("click", function (event) {
            if (event.target === adminServiceThumbsBackdrop) {
                closeAdminServiceThumbsModal();
            }
        });
    }

    if (adminServiceThumbsForm) {
        adminServiceThumbsForm.addEventListener("submit", function () {
            syncAdminServiceThumbSelectionUI();
        });
    }

    function closeAdminEditModal() {
        if (!adminEditBackdrop) {
            return;
        }

        setAdminCropWorkspaceVisible(false);
        resetAdminCropState();

        adminEditBackdrop.hidden = true;
        document.body.classList.remove("admin-modal-open");
        activeAdminEditCard = null;
    }

    function openAdminEditModal(card) {
        if (!adminEditBackdrop || !adminEditForm || !card) {
            return;
        }

        activeAdminEditCard = card;
        ensureCardBrandNameData(card);

        var cardImage = card.querySelector(".product-visual-image");
        var titleLink = card.querySelector(".product-title-link");
        var currentBrandValue = normalizeBrandValue(card.getAttribute("data-brand"));
        var storedProductName = String(card.getAttribute("data-product-name") || "").trim();
        var copyParagraphs = card.querySelectorAll(".product-copy > p");
        var specOne = copyParagraphs[0] ? copyParagraphs[0].textContent.trim() : "";
        var specTwo = copyParagraphs[1] ? copyParagraphs[1].textContent.trim() : "";
        var displayedTitle = titleLink ? titleLink.textContent.trim() : "";
        var productNameValue = storedProductName || splitProductDisplayName(currentBrandValue, displayedTitle);
        var priceParagraph = copyParagraphs[2] || null;
        var priceOriginal = 0;
        var discountPercent = 0;

        if (priceParagraph) {
            var spans = priceParagraph.querySelectorAll("span");

            if (spans.length >= 2) {
                priceOriginal = parseMoneyValue(spans[0].textContent);
                var discountedPrice = parseMoneyValue(spans[1].textContent);

                if (priceOriginal > 0 && discountedPrice > 0 && discountedPrice < priceOriginal) {
                    discountPercent = Math.round((1 - (discountedPrice / priceOriginal)) * 100);
                }
            } else {
                priceOriginal = parseMoneyValue(priceParagraph.textContent);
            }
        }

        if (adminEditPreviewImage) {
            adminEditPreviewImage.src = cardImage ? cardImage.src : "";
        }

        if (adminEditBrand) {
            adminEditBrand.value = currentBrandValue;

            if (adminEditBrand.value !== currentBrandValue) {
                adminEditBrand.value = defaultBrandValue;
                currentBrandValue = normalizeBrandValue(adminEditBrand.value);
            }

            adminEditBrand.setAttribute("data-admin-brand-last-value", currentBrandValue);
        }

        if (adminEditName) {
            adminEditName.value = productNameValue;
        }

        if (adminEditSpec1) {
            adminEditSpec1.value = specOne;
        }

        if (adminEditSpec2) {
            adminEditSpec2.value = specTwo;
        }

        if (adminEditPrice) {
            adminEditPrice.value = priceOriginal > 0 ? String(priceOriginal.toFixed(2)) : "";
        }

        if (adminEditDiscount) {
            adminEditDiscount.value = String(discountPercent);
        }

        var activeProductData = getActiveAdminProductData() || {};
        var specsMap = activeProductData.specs && typeof activeProductData.specs === "object" ? activeProductData.specs : {};

        if (adminEditTagline) {
            adminEditTagline.value = String(activeProductData.tagline || "");
        }

        if (adminEditImagingSpecs) {
            adminEditImagingSpecs.value = linesToTextarea(specsMap["Imaging and Performance"] || []);
        }

        if (adminEditVideoSpecs) {
            adminEditVideoSpecs.value = linesToTextarea(specsMap.Video || []);
        }

        if (adminEditPhysicalSpecs) {
            adminEditPhysicalSpecs.value = linesToTextarea(specsMap["Physical Specifications"] || []);
        }

        if (adminEditCaptureSlides) {
            adminEditCaptureSlides.value = linesToTextarea(activeProductData.captureSlides || []);
        }

        if (adminEditFileInput) {
            adminEditFileInput.value = "";
        }

        adminCropState.previewBeforeCrop = adminEditPreviewImage ? adminEditPreviewImage.src : "";
        adminCropState.sourceImage = "";
        resetAdminCropState();
        setAdminCropWorkspaceVisible(false);

        adminEditBackdrop.hidden = false;
        document.body.classList.add("admin-modal-open");
    }

    if (adminEditZoom) {
        adminEditZoom.addEventListener("input", function () {
            adminCropState.zoom = Number.parseFloat(adminEditZoom.value) || 1;
            var clamped = clampAdminCropOffsets(adminCropState.offsetX, adminCropState.offsetY);
            adminCropState.offsetX = clamped.x;
            adminCropState.offsetY = clamped.y;
            syncAdminPreviewTransform();
        });
    }

    if (adminEditBrand) {
        adminEditBrand.addEventListener("focus", function () {
            var currentValue = String(adminEditBrand.value || "").toLowerCase().trim();

            if (currentValue && currentValue !== manageBrandsOptionValue) {
                adminEditBrand.setAttribute("data-admin-brand-last-value", currentValue);
            }
        });

        adminEditBrand.addEventListener("change", function () {
            var selectedValue = String(adminEditBrand.value || "").toLowerCase().trim();

            if (selectedValue && selectedValue !== manageBrandsOptionValue) {
                adminEditBrand.setAttribute("data-admin-brand-last-value", selectedValue);
                return;
            }

            if (selectedValue !== manageBrandsOptionValue) {
                return;
            }

            var targetUrl = String(adminEditBrand.getAttribute("data-admin-manage-brands-url") || "").trim();
            var fallbackValue = String(adminEditBrand.getAttribute("data-admin-brand-last-value") || defaultBrandValue).toLowerCase().trim();

            if (fallbackValue && fallbackValue !== manageBrandsOptionValue) {
                adminEditBrand.value = fallbackValue;
            }

            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });
    }

    if (adminEditBrowse && adminEditFileInput) {
        adminEditBrowse.addEventListener("click", function () {
            adminEditFileInput.click();
        });
    }

    if (adminEditRecrop && adminEditPreviewImage) {
        adminEditRecrop.addEventListener("click", function () {
            if (!adminEditPreviewImage.src) {
                return;
            }

            adminCropState.previewBeforeCrop = adminEditPreviewImage.src;
            adminCropState.sourceImage = adminEditPreviewImage.src;
            resetAdminCropState();
            setAdminCropWorkspaceVisible(true);
            syncAdminPreviewTransform();
        });
    }

    if (adminEditFileInput && adminEditPreviewImage) {
        adminEditPreviewImage.addEventListener("dragstart", function (event) {
            event.preventDefault();
        });

        adminEditFileInput.addEventListener("change", function () {
            var file = adminEditFileInput.files && adminEditFileInput.files[0] ? adminEditFileInput.files[0] : null;

            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                adminCropState.previewBeforeCrop = adminEditPreviewImage.src || "";
                adminCropState.sourceImage = String(event.target && event.target.result ? event.target.result : "");
                adminEditPreviewImage.src = adminCropState.sourceImage;
                resetAdminCropState();
                setAdminCropWorkspaceVisible(true);
                syncAdminPreviewTransform();
            };
            reader.readAsDataURL(file);
        });
    }

    if (adminEditPreviewWrap) {
        adminEditPreviewWrap.addEventListener("wheel", function (event) {
            if (!adminCropState.isCropping || !adminEditZoom) {
                return;
            }

            event.preventDefault();

            var minZoom = Number.parseFloat(adminEditZoom.min || "1");
            var maxZoom = Number.parseFloat(adminEditZoom.max || "3");
            var stepZoom = Number.parseFloat(adminEditZoom.step || "0.01");
            var direction = event.deltaY < 0 ? 1 : -1;
            var nextZoom = adminCropState.zoom + (direction * (stepZoom * 5));

            nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
            nextZoom = Math.round(nextZoom * 100) / 100;

            adminCropState.zoom = nextZoom;
            adminEditZoom.value = String(nextZoom);

            var clamped = clampAdminCropOffsets(adminCropState.offsetX, adminCropState.offsetY);
            adminCropState.offsetX = clamped.x;
            adminCropState.offsetY = clamped.y;
            syncAdminPreviewTransform();
        }, { passive: false });

        adminEditPreviewWrap.addEventListener("pointerdown", function (event) {
            if (!adminCropState.isCropping || event.button !== 0) {
                return;
            }

            event.preventDefault();

            adminCropState.isDragging = true;
            adminCropState.dragPointerId = event.pointerId;
            adminCropState.dragStartClientX = event.clientX;
            adminCropState.dragStartClientY = event.clientY;
            adminCropState.dragStartOffsetX = adminCropState.offsetX;
            adminCropState.dragStartOffsetY = adminCropState.offsetY;
            adminEditPreviewWrap.setPointerCapture(event.pointerId);
        });

        adminEditPreviewWrap.addEventListener("pointermove", function (event) {
            if (!adminCropState.isCropping || !adminCropState.isDragging || adminCropState.dragPointerId !== event.pointerId) {
                return;
            }

            var nextX = adminCropState.dragStartOffsetX + (event.clientX - adminCropState.dragStartClientX);
            var nextY = adminCropState.dragStartOffsetY + (event.clientY - adminCropState.dragStartClientY);
            var clamped = clampAdminCropOffsets(nextX, nextY);
            adminCropState.offsetX = clamped.x;
            adminCropState.offsetY = clamped.y;
            syncAdminPreviewTransform();
        });

        function stopAdminCropDrag(event) {
            if (!adminCropState.isDragging || adminCropState.dragPointerId !== event.pointerId) {
                return;
            }

            adminCropState.isDragging = false;
            adminCropState.dragPointerId = null;
            adminEditPreviewWrap.releasePointerCapture(event.pointerId);
        }

        adminEditPreviewWrap.addEventListener("pointerup", stopAdminCropDrag);
        adminEditPreviewWrap.addEventListener("pointercancel", stopAdminCropDrag);
    }

    if (adminEditCropCancel && adminEditPreviewImage) {
        adminEditCropCancel.addEventListener("click", function () {
            adminEditPreviewImage.src = adminCropState.previewBeforeCrop || adminEditPreviewImage.src;
            adminCropState.sourceImage = "";
            resetAdminCropState();
            setAdminCropWorkspaceVisible(false);
        });
    }

    if (adminEditCropSave && adminEditPreviewImage) {
        adminEditCropSave.addEventListener("click", function () {
            var croppedDataUrl = buildAdminCropDataUrlFromPreview();

            if (!croppedDataUrl) {
                return;
            }

            adminEditPreviewImage.src = croppedDataUrl;
            adminCropState.previewBeforeCrop = croppedDataUrl;
            adminCropState.sourceImage = "";
            resetAdminCropState();
            setAdminCropWorkspaceVisible(false);
        });
    }

    adminEditButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var card = button.closest(".product-card");
            openAdminEditModal(card);
        });
    });

    if (adminEditClose) {
        adminEditClose.addEventListener("click", closeAdminEditModal);
    }

    if (adminEditCancel) {
        adminEditCancel.addEventListener("click", closeAdminEditModal);
    }

    if (adminEditBackdrop) {
        adminEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEditBackdrop) {
                closeAdminEditModal();
            }
        });
    }

    if (adminEditDuplicate) {
        adminEditDuplicate.addEventListener("click", function () {
            if (!activeAdminEditCard || !adminEditBackdrop) {
                return;
            }

            var productKey = activeAdminEditCard.getAttribute("data-product-key");
            var endpoint = adminEditBackdrop.getAttribute("data-admin-duplicate-endpoint") || "";

            if (!productKey || !endpoint) {
                return;
            }

            adminEditDuplicate.disabled = true;
            adminEditDuplicate.textContent = "Duplicating...";

            fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    productKey: productKey
                })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to duplicate product.";
                        throw new Error(message);
                    }

                    window.location.reload();
                })
                .catch(function (error) {
                    window.alert(error.message || "Unable to duplicate product.");
                    adminEditDuplicate.disabled = false;
                    adminEditDuplicate.textContent = "Duplicate Product";
                });
        });
    }

    if (adminEditForm) {
        adminEditForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (adminCropState.isCropping) {
                return;
            }

            if (!activeAdminEditCard || !adminEditBackdrop) {
                closeAdminEditModal();
                return;
            }

            var productKey = activeAdminEditCard.getAttribute("data-product-key") || "";
            var updateEndpoint = adminEditBackdrop.getAttribute("data-admin-update-endpoint") || "";
            var selectedBrandValue = adminEditBrand ? String(adminEditBrand.value || "").toLowerCase().trim() : "";

            if (selectedBrandValue === manageBrandsOptionValue) {
                var manageBrandsUrl = adminEditBrand
                    ? String(adminEditBrand.getAttribute("data-admin-manage-brands-url") || "").trim()
                    : "";

                if (manageBrandsUrl) {
                    window.location.href = manageBrandsUrl;
                }

                return;
            }

            var brandValue = normalizeBrandValue(selectedBrandValue || defaultBrandValue);
            var nameValue = adminEditName ? adminEditName.value.trim() : "";
            var specOneValue = adminEditSpec1 ? adminEditSpec1.value.trim() : "";
            var specTwoValue = adminEditSpec2 ? adminEditSpec2.value.trim() : "";
            var priceValue = adminEditPrice ? Number.parseFloat(adminEditPrice.value) : 0;
            var discountValue = clampDiscount(adminEditDiscount ? adminEditDiscount.value : 0);
            if (!productKey || !updateEndpoint || !nameValue || !specOneValue || !specTwoValue || !Number.isFinite(priceValue) || priceValue < 0) {
                return;
            }

            var finalPreviewSrc = adminEditPreviewImage ? adminEditPreviewImage.src : "";

            if (adminCropState.isCropping) {
                var autoCroppedDataUrl = buildAdminCropDataUrlFromPreview();

                if (autoCroppedDataUrl && adminEditPreviewImage) {
                    finalPreviewSrc = autoCroppedDataUrl;
                    adminEditPreviewImage.src = autoCroppedDataUrl;
                    adminCropState.previewBeforeCrop = autoCroppedDataUrl;
                }

                adminCropState.sourceImage = "";
                resetAdminCropState();
                setAdminCropWorkspaceVisible(false);
            }

            var submitButton = adminEditForm.querySelector('button[type="submit"]');
            var previousSubmitText = submitButton ? submitButton.textContent : "";

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Saving...";
            }

            var payload = {
                productKey: productKey,
                brand: brandValue,
                name: nameValue,
                spec1: specOneValue,
                spec2: specTwoValue,
                price: priceValue,
                discountPercent: discountValue,
                imageDataUrl: finalPreviewSrc
            };

            fetch(updateEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to save product changes.";
                        throw new Error(message);
                    }

                    window.location.reload();
                })
                .catch(function (error) {
                    window.alert(error.message || "Unable to save product changes.");

                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = previousSubmitText || "Save Changes";
                    }
                });
        });
    }

    function setAdminHowPreviewSource(source) {
        if (!adminHowPreviewImage) {
            return;
        }

        var nextSource = String(source || "").trim();

        if (!nextSource) {
            adminHowPreviewImage.hidden = true;
            adminHowPreviewImage.removeAttribute("src");
            return;
        }

        adminHowPreviewImage.hidden = false;
        adminHowPreviewImage.src = nextSource;
    }

    function syncAdminHowPreviewTransform() {
        if (!adminHowPreviewWrap || !adminHowPreviewImage || !adminHowZoom) {
            return;
        }

        var bounds = getCoverRenderBounds(adminHowPreviewWrap, adminHowPreviewImage, adminHowCropState.zoom);
        var coverScale = adminHowPreviewImage.naturalWidth > 0
            ? (bounds.renderedWidth / adminHowPreviewImage.naturalWidth)
            : 1;

        adminHowPreviewWrap.style.setProperty("--admin-crop-zoom", String(adminHowCropState.zoom));
        adminHowPreviewWrap.style.setProperty("--admin-crop-x", String(adminHowCropState.offsetX) + "px");
        adminHowPreviewWrap.style.setProperty("--admin-crop-y", String(adminHowCropState.offsetY) + "px");
        adminHowPreviewWrap.style.setProperty("--admin-crop-scale", String(coverScale));
        adminHowPreviewWrap.classList.toggle("is-crop-active", adminHowCropState.isCropping);
    }

    function setAdminHowCropWorkspaceVisible(isVisible) {
        adminHowCropState.isCropping = isVisible;

        if (adminHowCropWorkspace) {
            adminHowCropWorkspace.hidden = !isVisible;
        }

        if (adminHowImageActions) {
            adminHowImageActions.hidden = isVisible;

            Array.prototype.forEach.call(adminHowImageActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        if (adminHowMainActions) {
            adminHowMainActions.hidden = isVisible;

            Array.prototype.forEach.call(adminHowMainActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        syncAdminHowPreviewTransform();
    }

    function clampAdminHowCropOffsets(nextX, nextY) {
        if (!adminHowPreviewWrap || !adminHowPreviewImage) {
            return { x: nextX, y: nextY };
        }

        var bounds = getCoverRenderBounds(adminHowPreviewWrap, adminHowPreviewImage, adminHowCropState.zoom);
        var clampedX = Math.min(bounds.maxShiftX, Math.max(-bounds.maxShiftX, nextX));
        var clampedY = Math.min(bounds.maxShiftY, Math.max(-bounds.maxShiftY, nextY));

        return {
            x: clampedX,
            y: clampedY
        };
    }

    function resetAdminHowCropState() {
        adminHowCropState.zoom = 1;
        adminHowCropState.offsetX = 0;
        adminHowCropState.offsetY = 0;
        adminHowCropState.isDragging = false;
        adminHowCropState.dragPointerId = null;

        if (adminHowZoom) {
            adminHowZoom.value = "1";
        }

        syncAdminHowPreviewTransform();
    }

    function buildAdminHowCropDataUrlFromPreview() {
        if (!adminHowPreviewImage || adminHowPreviewImage.hidden || !adminHowPreviewImage.src || !adminHowPreviewImage.naturalWidth || !adminHowPreviewImage.naturalHeight) {
            return null;
        }

        var outputWidth = 900;
        var outputHeight = Math.round(outputWidth * (adminHowAspect.height / adminHowAspect.width));
        var canvas = document.createElement("canvas");
        canvas.width = outputWidth;
        canvas.height = outputHeight;

        var ctx = canvas.getContext("2d");
        if (!ctx) {
            return null;
        }

        var zoomValue = Math.max(1, Number(adminHowCropState.zoom || 1));
        var bounds = getCoverRenderBounds(adminHowPreviewWrap, adminHowPreviewImage, zoomValue);
        var offsetScaleX = bounds.rectWidth > 0 ? (outputWidth / bounds.rectWidth) : 1;
        var offsetScaleY = bounds.rectHeight > 0 ? (outputHeight / bounds.rectHeight) : 1;
        var scaleToCover = Math.max(outputWidth / adminHowPreviewImage.naturalWidth, outputHeight / adminHowPreviewImage.naturalHeight);
        var scale = scaleToCover * zoomValue;
        var drawWidth = adminHowPreviewImage.naturalWidth * scale;
        var drawHeight = adminHowPreviewImage.naturalHeight * scale;
        var drawX = ((outputWidth - drawWidth) / 2) + (adminHowCropState.offsetX * offsetScaleX);
        var drawY = ((outputHeight - drawHeight) / 2) + (adminHowCropState.offsetY * offsetScaleY);

        ctx.clearRect(0, 0, outputWidth, outputHeight);
        ctx.drawImage(adminHowPreviewImage, drawX, drawY, drawWidth, drawHeight);

        return canvas.toDataURL("image/png");
    }

    function updateAdminHowRecropState() {
        if (!adminHowRecrop || !adminHowPreviewImage) {
            return;
        }

        adminHowRecrop.disabled = adminHowPreviewImage.hidden || !adminHowPreviewImage.src;
    }

    function closeAdminHowEditModal() {
        if (!adminHowEditBackdrop) {
            return;
        }

        setAdminHowCropWorkspaceVisible(false);
        resetAdminHowCropState();
        adminHowCropState.previewBeforeCrop = "";
        adminHowCropState.sourceImage = "";

        if (adminHowFileInput) {
            adminHowFileInput.value = "";
        }

        setAdminHowPreviewSource("");
        updateAdminHowRecropState();
        activeAdminHowSlot = "";
        adminHowEditBackdrop.hidden = true;
        document.body.classList.remove("admin-modal-open");
    }

    function openAdminHowEditModal(slot, currentSource) {
        if (!adminHowEditBackdrop || !adminHowForm) {
            return;
        }

        activeAdminHowSlot = String(slot || "").trim();
        if (!activeAdminHowSlot) {
            return;
        }

        if (adminHowSlotNote) {
            adminHowSlotNote.textContent = "Slot " + activeAdminHowSlot + " image (3:2)";
        }

        setAdminHowPreviewSource(currentSource || "");
        updateAdminHowRecropState();

        if (adminHowFileInput) {
            adminHowFileInput.value = "";
        }

        adminHowCropState.previewBeforeCrop = adminHowPreviewImage && !adminHowPreviewImage.hidden ? adminHowPreviewImage.src : "";
        adminHowCropState.sourceImage = "";
        resetAdminHowCropState();
        setAdminHowCropWorkspaceVisible(false);

        adminHowEditBackdrop.hidden = false;
        document.body.classList.add("admin-modal-open");
    }

    function renderAdminHowCardAsEmpty(card, slotValue) {
        if (!card) {
            return;
        }

        var slot = String(slotValue || card.getAttribute("data-admin-how-slot") || "").trim();
        card.classList.add("step-card-admin-add");
        card.classList.remove("is-admin-busy");
        card.setAttribute("data-admin-how-has-image", "false");
        card.setAttribute("data-admin-how-image-src", "");
        card.innerHTML = '' +
            '<button class="step-card-admin-add-trigger" type="button" data-admin-how-edit aria-label="Add how it works image slot ' + slot + '">' +
                '<span class="step-card-admin-add-plus">+</span>' +
                '<span>Add Photo</span>' +
            '</button>';
    }

    function renderAdminHowCardAsImage(card, slotValue, imageSource) {
        if (!card) {
            return;
        }

        var slot = String(slotValue || card.getAttribute("data-admin-how-slot") || "").trim();
        var cacheBustedSource = String(imageSource || "");

        card.classList.remove("step-card-admin-add");
        card.classList.remove("is-admin-busy");
        card.setAttribute("data-admin-how-has-image", "true");
        card.setAttribute("data-admin-how-image-src", cacheBustedSource);
        card.innerHTML = '' +
            '<button class="step-card-admin-remove" type="button" data-admin-how-remove aria-label="Delete how it works image slot ' + slot + '">&times;</button>' +
            '<button class="step-card-admin-image-button" type="button" data-admin-how-edit aria-label="Edit how it works image slot ' + slot + '">' +
                '<img class="step-image" src="' + cacheBustedSource + '" alt="How it works step ' + slot + '">' +
            '</button>';
    }

    if (adminHowGrid) {
        var adminHowUpdateEndpoint = adminHowGrid.getAttribute("data-admin-how-update-endpoint") || "";
        var adminHowDeleteEndpoint = adminHowGrid.getAttribute("data-admin-how-delete-endpoint") || "";

        adminHowGrid.addEventListener("click", function (event) {
            var removeButton = event.target.closest("[data-admin-how-remove]");
            if (removeButton) {
                var removeCard = removeButton.closest("[data-admin-how-slot]");
                var removeSlot = removeCard ? (removeCard.getAttribute("data-admin-how-slot") || "") : "";

                if (!removeCard || !removeSlot || !adminHowDeleteEndpoint) {
                    return;
                }

                removeCard.classList.add("is-admin-busy");
                removeButton.disabled = true;

                fetch(adminHowDeleteEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        slot: Number.parseInt(removeSlot, 10)
                    })
                })
                    .then(function (response) {
                        return response.json().then(function (payload) {
                            return {
                                ok: response.ok,
                                payload: payload
                            };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.payload || !result.payload.ok) {
                            var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive image.";
                            throw new Error(message);
                        }

                        var archivedEntry = result.payload.archivedEntry && typeof result.payload.archivedEntry === "object"
                            ? result.payload.archivedEntry
                            : null;
                        var archivedAt = archivedEntry && archivedEntry.archivedAt ? String(archivedEntry.archivedAt) : "";
                        var archiveKey = archivedEntry && archivedEntry.archiveKey ? String(archivedEntry.archiveKey) : "";

                        renderAdminHowCardAsEmpty(removeCard, removeSlot);

                        if (archiveKey) {
                            showAdminUndoToast(
                                "How It Works slot " + removeSlot + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""),
                                {
                                    type: "how",
                                    archiveKey: archiveKey,
                                    onRestore: function (payload) {
                                        var restoredSlot = payload && payload.slot ? String(payload.slot) : removeSlot;
                                        var restoredSource = adminHowImageBase + restoredSlot + ".png?t=" + String(Date.now());
                                        renderAdminHowCardAsImage(removeCard, restoredSlot, restoredSource);
                                    }
                                }
                            );
                        }
                    })
                    .catch(function (error) {
                        removeCard.classList.remove("is-admin-busy");
                        removeButton.disabled = false;
                        window.alert(error.message || "Unable to archive image.");
                    });

                return;
            }

            var editButton = event.target.closest("[data-admin-how-edit]");
            if (!editButton) {
                return;
            }

            var editCard = editButton.closest("[data-admin-how-slot]");
            var editSlot = editCard ? (editCard.getAttribute("data-admin-how-slot") || "") : "";
            var currentSource = editCard ? (editCard.getAttribute("data-admin-how-image-src") || "") : "";

            openAdminHowEditModal(editSlot, currentSource);
        });

        if (adminHowZoom) {
            adminHowZoom.addEventListener("input", function () {
                adminHowCropState.zoom = Number.parseFloat(adminHowZoom.value) || 1;
                var clamped = clampAdminHowCropOffsets(adminHowCropState.offsetX, adminHowCropState.offsetY);
                adminHowCropState.offsetX = clamped.x;
                adminHowCropState.offsetY = clamped.y;
                syncAdminHowPreviewTransform();
            });
        }

        if (adminHowBrowse && adminHowFileInput) {
            adminHowBrowse.addEventListener("click", function () {
                adminHowFileInput.click();
            });
        }

        if (adminHowRecrop && adminHowPreviewImage) {
            adminHowRecrop.addEventListener("click", function () {
                if (adminHowPreviewImage.hidden || !adminHowPreviewImage.src) {
                    return;
                }

                adminHowCropState.previewBeforeCrop = adminHowPreviewImage.src;
                adminHowCropState.sourceImage = adminHowPreviewImage.src;
                resetAdminHowCropState();
                setAdminHowCropWorkspaceVisible(true);
                syncAdminHowPreviewTransform();
            });
        }

        if (adminHowFileInput && adminHowPreviewImage) {
            adminHowPreviewImage.addEventListener("dragstart", function (event) {
                event.preventDefault();
            });

            adminHowFileInput.addEventListener("change", function () {
                var file = adminHowFileInput.files && adminHowFileInput.files[0] ? adminHowFileInput.files[0] : null;

                if (!file) {
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (loadEvent) {
                    adminHowCropState.previewBeforeCrop = adminHowPreviewImage.hidden ? "" : (adminHowPreviewImage.src || "");
                    adminHowCropState.sourceImage = String(loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");
                    setAdminHowPreviewSource(adminHowCropState.sourceImage);
                    updateAdminHowRecropState();
                    resetAdminHowCropState();
                    setAdminHowCropWorkspaceVisible(true);
                    syncAdminHowPreviewTransform();
                };
                reader.readAsDataURL(file);
            });
        }

        if (adminHowPreviewWrap) {
            adminHowPreviewWrap.addEventListener("wheel", function (event) {
                if (!adminHowCropState.isCropping || !adminHowZoom) {
                    return;
                }

                event.preventDefault();

                var minZoom = Number.parseFloat(adminHowZoom.min || "1");
                var maxZoom = Number.parseFloat(adminHowZoom.max || "3");
                var stepZoom = Number.parseFloat(adminHowZoom.step || "0.01");
                var direction = event.deltaY < 0 ? 1 : -1;
                var nextZoom = adminHowCropState.zoom + (direction * (stepZoom * 5));

                nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
                nextZoom = Math.round(nextZoom * 100) / 100;

                adminHowCropState.zoom = nextZoom;
                adminHowZoom.value = String(nextZoom);

                var clamped = clampAdminHowCropOffsets(adminHowCropState.offsetX, adminHowCropState.offsetY);
                adminHowCropState.offsetX = clamped.x;
                adminHowCropState.offsetY = clamped.y;
                syncAdminHowPreviewTransform();
            }, { passive: false });

            adminHowPreviewWrap.addEventListener("pointerdown", function (event) {
                if (!adminHowCropState.isCropping || event.button !== 0) {
                    return;
                }

                event.preventDefault();

                adminHowCropState.isDragging = true;
                adminHowCropState.dragPointerId = event.pointerId;
                adminHowCropState.dragStartClientX = event.clientX;
                adminHowCropState.dragStartClientY = event.clientY;
                adminHowCropState.dragStartOffsetX = adminHowCropState.offsetX;
                adminHowCropState.dragStartOffsetY = adminHowCropState.offsetY;
                adminHowPreviewWrap.setPointerCapture(event.pointerId);
            });

            adminHowPreviewWrap.addEventListener("pointermove", function (event) {
                if (!adminHowCropState.isCropping || !adminHowCropState.isDragging || adminHowCropState.dragPointerId !== event.pointerId) {
                    return;
                }

                var nextX = adminHowCropState.dragStartOffsetX + (event.clientX - adminHowCropState.dragStartClientX);
                var nextY = adminHowCropState.dragStartOffsetY + (event.clientY - adminHowCropState.dragStartClientY);
                var clamped = clampAdminHowCropOffsets(nextX, nextY);
                adminHowCropState.offsetX = clamped.x;
                adminHowCropState.offsetY = clamped.y;
                syncAdminHowPreviewTransform();
            });

            function stopAdminHowCropDrag(event) {
                if (!adminHowCropState.isDragging || adminHowCropState.dragPointerId !== event.pointerId) {
                    return;
                }

                adminHowCropState.isDragging = false;
                adminHowCropState.dragPointerId = null;
                adminHowPreviewWrap.releasePointerCapture(event.pointerId);
            }

            adminHowPreviewWrap.addEventListener("pointerup", stopAdminHowCropDrag);
            adminHowPreviewWrap.addEventListener("pointercancel", stopAdminHowCropDrag);
        }

        if (adminHowCropCancel && adminHowPreviewImage) {
            adminHowCropCancel.addEventListener("click", function () {
                setAdminHowPreviewSource(adminHowCropState.previewBeforeCrop || "");
                updateAdminHowRecropState();
                adminHowCropState.sourceImage = "";
                resetAdminHowCropState();
                setAdminHowCropWorkspaceVisible(false);
            });
        }

        if (adminHowCropSave && adminHowPreviewImage) {
            adminHowCropSave.addEventListener("click", function () {
                var croppedDataUrl = buildAdminHowCropDataUrlFromPreview();

                if (!croppedDataUrl) {
                    return;
                }

                setAdminHowPreviewSource(croppedDataUrl);
                adminHowCropState.previewBeforeCrop = croppedDataUrl;
                adminHowCropState.sourceImage = "";
                updateAdminHowRecropState();
                resetAdminHowCropState();
                setAdminHowCropWorkspaceVisible(false);
            });
        }

        if (adminHowClose) {
            adminHowClose.addEventListener("click", closeAdminHowEditModal);
        }

        if (adminHowCancel) {
            adminHowCancel.addEventListener("click", closeAdminHowEditModal);
        }

        if (adminHowEditBackdrop) {
            adminHowEditBackdrop.addEventListener("click", function (event) {
                if (event.target === adminHowEditBackdrop) {
                    closeAdminHowEditModal();
                }
            });
        }

        if (adminHowForm) {
            adminHowForm.addEventListener("submit", function (event) {
                event.preventDefault();

                if (!activeAdminHowSlot || !adminHowUpdateEndpoint) {
                    return;
                }

                var finalPreviewSrc = adminHowPreviewImage && !adminHowPreviewImage.hidden ? adminHowPreviewImage.src : "";

                if (adminHowCropState.isCropping) {
                    var autoCroppedDataUrl = buildAdminHowCropDataUrlFromPreview();

                    if (autoCroppedDataUrl) {
                        finalPreviewSrc = autoCroppedDataUrl;
                        setAdminHowPreviewSource(autoCroppedDataUrl);
                        adminHowCropState.previewBeforeCrop = autoCroppedDataUrl;
                    }

                    adminHowCropState.sourceImage = "";
                    resetAdminHowCropState();
                    setAdminHowCropWorkspaceVisible(false);
                }

                if (!finalPreviewSrc) {
                    window.alert("Please add an image first.");
                    return;
                }

                var submitButton = adminHowForm.querySelector('button[type="submit"]');
                var previousSubmitTitle = submitButton ? submitButton.getAttribute("title") : "";

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.setAttribute("title", "Saving...");
                }

                fetch(adminHowUpdateEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        slot: Number.parseInt(activeAdminHowSlot, 10),
                        imageDataUrl: finalPreviewSrc
                    })
                })
                    .then(function (response) {
                        return response.json().then(function (payload) {
                            return {
                                ok: response.ok,
                                payload: payload
                            };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.payload || !result.payload.ok) {
                            var message = result.payload && result.payload.message ? result.payload.message : "Unable to save image.";
                            throw new Error(message);
                        }

                        window.location.reload();
                    })
                    .catch(function (error) {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.setAttribute("title", previousSubmitTitle || "Save Changes");
                        }

                        window.alert(error.message || "Unable to save image.");
                    });
            });
        }

        updateAdminHowRecropState();
    }

    function getPromoSlides() {
        return promoBanner ? promoBanner.querySelectorAll(".promo-slide") : [];
    }

    function getPromoImageSlides() {
        return promoBanner ? promoBanner.querySelectorAll(".promo-slide[data-promo-slot]") : [];
    }

    function getPromoAddSlide() {
        return promoBanner ? promoBanner.querySelector("[data-admin-promo-add-slide]") : null;
    }

    function getActivePromoSlide() {
        if (!promoBanner) {
            return null;
        }

        var activeSlide = promoBanner.querySelector(".promo-slide.is-active");
        if (activeSlide) {
            return activeSlide;
        }

        var promoSlides = getPromoSlides();

        return promoSlides.length ? promoSlides[0] : null;
    }

    function syncAdminPromoRemoveState() {
        if (!adminPromoRemove) {
            return;
        }

        var activeSlide = getActivePromoSlide();
        var hasImageSlides = getPromoImageSlides().length > 0;
        var activeHasSlot = !!(activeSlide && String(activeSlide.getAttribute("data-promo-slot") || "").trim());

        adminPromoRemove.disabled = !hasImageSlides || !activeHasSlot;
    }

    function showPromoSlide(nextIndex) {
        var promoSlides = getPromoSlides();

        if (!promoSlides.length) {
            promoIndex = 0;
            syncAdminPromoRemoveState();
            return;
        }

        promoIndex = (nextIndex + promoSlides.length) % promoSlides.length;

        promoSlides.forEach(function (slide, slideIndex) {
            var isActive = slideIndex === promoIndex;

            slide.classList.toggle("is-active", isActive);
            slide.setAttribute("aria-hidden", isActive ? "false" : "true");
        });

        syncAdminPromoRemoveState();
    }

    function startPromoTimer() {
        var promoSlides = getPromoSlides();

        if (promoSlides.length < 2) {
            return;
        }

        window.clearInterval(promoTimer);
        promoTimer = window.setInterval(function () {
            showPromoSlide(promoIndex + 1);
        }, promoDelay);
    }

    function stopPromoTimer() {
        window.clearInterval(promoTimer);
        promoTimer = null;
    }

    function getNextAvailablePromoSlot() {
        var slots = [];

        Array.prototype.forEach.call(getPromoImageSlides(), function (slide) {
            var value = Number.parseInt(slide.getAttribute("data-promo-slot") || "0", 10);

            if (Number.isFinite(value) && value >= 1) {
                slots.push(value);
            }
        });

        if (!slots.length) {
            return 1;
        }

        return Math.max.apply(null, slots) + 1;
    }

    function updatePromoAddSlideSlot() {
        var addSlide = getPromoAddSlide();

        if (!addSlide) {
            return;
        }

        var nextSlot = getNextAvailablePromoSlot();

        addSlide.setAttribute("data-admin-promo-slot", String(nextSlot));
    }

    function updatePromoControls() {
        var promoSlides = getPromoSlides();
        var imageSlides = getPromoImageSlides();
        var hasSlides = promoSlides.length > 0;
        var isSingleSlide = promoSlides.length <= 1;

        if (promoPrev) {
            promoPrev.hidden = isSingleSlide;
        }

        if (promoNext) {
            promoNext.hidden = isSingleSlide;
        }

        if (!promoCarousel) {
            syncAdminPromoRemoveState();
            return;
        }

        var emptyNode = promoCarousel.querySelector("[data-promo-empty]");

        if (!hasSlides) {
            stopPromoTimer();

            if (!adminPromoBanner && !emptyNode) {
                emptyNode = document.createElement("div");
                emptyNode.className = "promo-placeholder promo-placeholder-empty";
                emptyNode.setAttribute("data-promo-empty", "true");
                emptyNode.innerHTML = "<span>No promo banners available.</span>";
                promoCarousel.appendChild(emptyNode);
            }

            syncAdminPromoRemoveState();
            return;
        }

        if (emptyNode) {
            emptyNode.remove();
        }

        if (adminPromoBanner && !imageSlides.length) {
            var addSlide = getPromoAddSlide();
            var slidesArray = Array.prototype.slice.call(getPromoSlides());
            var addSlideIndex = addSlide ? slidesArray.indexOf(addSlide) : -1;

            if (addSlideIndex >= 0) {
                showPromoSlide(addSlideIndex);
            }
        } else {
            syncAdminPromoRemoveState();
        }
    }

    function buildPromoSlotFilename(slot) {
        var slotNumber = Number.parseInt(slot, 10);

        if (!Number.isFinite(slotNumber) || slotNumber < 1) {
            slotNumber = 1;
        }

        return String(slotNumber).padStart(4, "0") + ".png";
    }

    function buildPromoSlideClass(slot) {
        var slotNumber = Number.parseInt(slot, 10);

        if (slotNumber === 1) {
            return "promo-slide-one";
        }

        if (slotNumber === 2) {
            return "promo-slide-two";
        }

        return "promo-slide-three";
    }

    function insertPromoSlideSorted(slideElement) {
        if (!promoCarousel || !slideElement) {
            return;
        }

        var slotValue = Number.parseInt(slideElement.getAttribute("data-promo-slot") || "0", 10);
        var promoSlides = Array.prototype.slice.call(promoCarousel.querySelectorAll(".promo-slide"));
        var inserted = false;

        var addSlide = getPromoAddSlide();
        if (addSlide && addSlide !== slideElement) {
            promoCarousel.insertBefore(slideElement, addSlide);
            inserted = true;
        }

        promoSlides.forEach(function (existingSlide) {
            if (inserted) {
                return;
            }

            var existingSlot = Number.parseInt(existingSlide.getAttribute("data-promo-slot") || "0", 10);

            if (Number.isFinite(existingSlot) && existingSlot > slotValue) {
                promoCarousel.insertBefore(slideElement, existingSlide);
                inserted = true;
            }
        });

        if (!inserted) {
            promoCarousel.appendChild(slideElement);
        }
    }

    function setAdminPromoPreviewSource(source) {
        if (!adminPromoPreviewImage) {
            return;
        }

        var nextSource = String(source || "").trim();

        if (!nextSource) {
            adminPromoPreviewImage.hidden = true;
            adminPromoPreviewImage.removeAttribute("src");
            return;
        }

        adminPromoPreviewImage.hidden = false;
        adminPromoPreviewImage.src = nextSource;
    }

    function syncAdminPromoPreviewTransform() {
        if (!adminPromoPreviewWrap || !adminPromoPreviewImage || !adminPromoZoom) {
            return;
        }

        var bounds = getCoverRenderBounds(adminPromoPreviewWrap, adminPromoPreviewImage, adminPromoCropState.zoom);
        var coverScale = adminPromoPreviewImage.naturalWidth > 0
            ? (bounds.renderedWidth / adminPromoPreviewImage.naturalWidth)
            : 1;

        adminPromoPreviewWrap.style.setProperty("--admin-crop-zoom", String(adminPromoCropState.zoom));
        adminPromoPreviewWrap.style.setProperty("--admin-crop-x", String(adminPromoCropState.offsetX) + "px");
        adminPromoPreviewWrap.style.setProperty("--admin-crop-y", String(adminPromoCropState.offsetY) + "px");
        adminPromoPreviewWrap.style.setProperty("--admin-crop-scale", String(coverScale));
        adminPromoPreviewWrap.classList.toggle("is-crop-active", adminPromoCropState.isCropping);
    }

    function setAdminPromoCropWorkspaceVisible(isVisible) {
        adminPromoCropState.isCropping = isVisible;

        if (adminPromoCropWorkspace) {
            adminPromoCropWorkspace.hidden = !isVisible;
        }

        if (adminPromoImageActions) {
            adminPromoImageActions.hidden = isVisible;

            Array.prototype.forEach.call(adminPromoImageActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        if (adminPromoMainActions) {
            adminPromoMainActions.hidden = isVisible;

            Array.prototype.forEach.call(adminPromoMainActions.querySelectorAll("button"), function (button) {
                button.disabled = isVisible;
            });
        }

        syncAdminPromoPreviewTransform();
    }

    function clampAdminPromoCropOffsets(nextX, nextY) {
        if (!adminPromoPreviewWrap || !adminPromoPreviewImage) {
            return { x: nextX, y: nextY };
        }

        var bounds = getCoverRenderBounds(adminPromoPreviewWrap, adminPromoPreviewImage, adminPromoCropState.zoom);
        var clampedX = Math.min(bounds.maxShiftX, Math.max(-bounds.maxShiftX, nextX));
        var clampedY = Math.min(bounds.maxShiftY, Math.max(-bounds.maxShiftY, nextY));

        return {
            x: clampedX,
            y: clampedY
        };
    }

    function resetAdminPromoCropState() {
        adminPromoCropState.zoom = 1;
        adminPromoCropState.offsetX = 0;
        adminPromoCropState.offsetY = 0;
        adminPromoCropState.isDragging = false;
        adminPromoCropState.dragPointerId = null;

        if (adminPromoZoom) {
            adminPromoZoom.value = "1";
        }

        syncAdminPromoPreviewTransform();
    }

    function updateAdminPromoRecropState() {
        if (!adminPromoRecrop || !adminPromoPreviewImage) {
            return;
        }

        adminPromoRecrop.disabled = adminPromoPreviewImage.hidden || !adminPromoPreviewImage.src;
    }

    function buildAdminPromoCropDataUrlFromPreview() {
        if (!adminPromoPreviewImage || adminPromoPreviewImage.hidden || !adminPromoPreviewImage.src || !adminPromoPreviewImage.naturalWidth || !adminPromoPreviewImage.naturalHeight) {
            return null;
        }

        var outputWidth = 1500;
        var outputHeight = Math.round(outputWidth * (adminPromoAspect.height / adminPromoAspect.width));
        var canvas = document.createElement("canvas");
        canvas.width = outputWidth;
        canvas.height = outputHeight;

        var ctx = canvas.getContext("2d");
        if (!ctx) {
            return null;
        }

        var zoomValue = Math.max(1, Number(adminPromoCropState.zoom || 1));
        var bounds = getCoverRenderBounds(adminPromoPreviewWrap, adminPromoPreviewImage, zoomValue);
        var offsetScaleX = bounds.rectWidth > 0 ? (outputWidth / bounds.rectWidth) : 1;
        var offsetScaleY = bounds.rectHeight > 0 ? (outputHeight / bounds.rectHeight) : 1;
        var scaleToCover = Math.max(outputWidth / adminPromoPreviewImage.naturalWidth, outputHeight / adminPromoPreviewImage.naturalHeight);
        var scale = scaleToCover * zoomValue;
        var drawWidth = adminPromoPreviewImage.naturalWidth * scale;
        var drawHeight = adminPromoPreviewImage.naturalHeight * scale;
        var drawX = ((outputWidth - drawWidth) / 2) + (adminPromoCropState.offsetX * offsetScaleX);
        var drawY = ((outputHeight - drawHeight) / 2) + (adminPromoCropState.offsetY * offsetScaleY);

        ctx.clearRect(0, 0, outputWidth, outputHeight);
        ctx.drawImage(adminPromoPreviewImage, drawX, drawY, drawWidth, drawHeight);

        return canvas.toDataURL("image/png");
    }

    function closeAdminPromoEditModal() {
        if (!adminPromoEditBackdrop) {
            return;
        }

        setAdminPromoCropWorkspaceVisible(false);
        resetAdminPromoCropState();
        adminPromoCropState.previewBeforeCrop = "";
        adminPromoCropState.sourceImage = "";

        if (adminPromoFileInput) {
            adminPromoFileInput.value = "";
        }

        setAdminPromoPreviewSource("");
        updateAdminPromoRecropState();
        activeAdminPromoSlot = "";
        adminPromoEditBackdrop.hidden = true;
        document.body.classList.remove("admin-modal-open");
    }

    function openAdminPromoEditModal(slot) {
        if (!adminPromoEditBackdrop || !adminPromoForm) {
            return;
        }

        activeAdminPromoSlot = String(slot || "").trim();
        if (!activeAdminPromoSlot) {
            return;
        }

        if (adminPromoSlotNote) {
            adminPromoSlotNote.textContent = "Promo slot " + activeAdminPromoSlot + " image (3:1)";
        }

        setAdminPromoPreviewSource("");
        updateAdminPromoRecropState();

        if (adminPromoFileInput) {
            adminPromoFileInput.value = "";
        }

        adminPromoCropState.previewBeforeCrop = "";
        adminPromoCropState.sourceImage = "";
        resetAdminPromoCropState();
        setAdminPromoCropWorkspaceVisible(false);

        adminPromoEditBackdrop.hidden = false;
        document.body.classList.add("admin-modal-open");
    }

    if (adminPromoBanner && adminPromoRemove) {
        adminPromoRemove.addEventListener("click", function () {
            if (!adminPromoArchiveEndpoint) {
                return;
            }

            var activeSlide = getActivePromoSlide();
            if (!activeSlide) {
                updatePromoControls();
                return;
            }

            var slotValue = (activeSlide.getAttribute("data-promo-slot") || "").trim();
            var slotNumber = Number.parseInt(slotValue, 10);

            if (!slotValue || !Number.isFinite(slotNumber) || slotNumber < 1) {
                return;
            }

            adminPromoBanner.classList.add("is-admin-busy");
            adminPromoRemove.disabled = true;

            fetch(adminPromoArchiveEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    slot: slotNumber
                })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to archive promo banner.";
                        throw new Error(message);
                    }

                    var archivedEntry = result.payload.archivedEntry && typeof result.payload.archivedEntry === "object"
                        ? result.payload.archivedEntry
                        : null;
                    var archivedAt = archivedEntry && archivedEntry.archivedAt ? String(archivedEntry.archivedAt) : "";
                    var archiveKey = archivedEntry && archivedEntry.archiveKey ? String(archivedEntry.archiveKey) : "";

                    activeSlide.remove();

                    updatePromoAddSlideSlot();

                    var promoSlides = getPromoSlides();
                    if (promoSlides.length) {
                        showPromoSlide(Math.min(promoIndex, promoSlides.length - 1));
                        stopPromoTimer();
                        startPromoTimer();
                    }

                    updatePromoControls();

                    if (archiveKey) {
                        showAdminUndoToast(
                            "Promo banner slot " + slotValue + " archived" + (archivedAt ? " (" + formatArchiveDateLabel(archivedAt) + ")" : ""),
                            {
                                type: "promo",
                                archiveKey: archiveKey,
                                button: adminPromoRemove,
                                onRestore: function (payload) {
                                    if (!promoCarousel) {
                                        return;
                                    }

                                    var restoredSlot = payload && payload.slot ? String(payload.slot) : slotValue;
                                    var restoredFilename = buildPromoSlotFilename(restoredSlot);
                                    var restoredSource = adminPromoImageBase + restoredFilename + "?t=" + String(Date.now());
                                    var slide = document.createElement("div");
                                    var slotClass = buildPromoSlideClass(restoredSlot);

                                    slide.className = "promo-slide " + slotClass;
                                    slide.setAttribute("data-promo-slot", restoredSlot);
                                    slide.innerHTML = '<img class="promo-image" src="' + restoredSource + '" alt="Promo banner slot ' + restoredSlot + '">';

                                    insertPromoSlideSorted(slide);
                                    updatePromoAddSlideSlot();

                                    var slidesAfterRestore = Array.prototype.slice.call(getPromoSlides());
                                    var restoredIndex = slidesAfterRestore.indexOf(slide);

                                    updatePromoControls();
                                    showPromoSlide(restoredIndex >= 0 ? restoredIndex : 0);
                                    stopPromoTimer();
                                    startPromoTimer();
                                }
                            }
                        );
                    }

                    adminPromoBanner.classList.remove("is-admin-busy");
                })
                .catch(function (error) {
                    adminPromoBanner.classList.remove("is-admin-busy");
                    updatePromoControls();
                    window.alert(error.message || "Unable to archive promo banner.");
                });
        });
    }

    if (promoCarousel) {
        promoCarousel.addEventListener("click", function (event) {
            var addButton = event.target.closest("[data-admin-promo-add-trigger]");

            if (!addButton) {
                return;
            }

            var addSlide = addButton.closest("[data-admin-promo-add-slide]");
            var slot = addSlide ? (addSlide.getAttribute("data-admin-promo-slot") || "") : "";

            openAdminPromoEditModal(slot);
        });
    }

    if (adminPromoZoom) {
        adminPromoZoom.addEventListener("input", function () {
            adminPromoCropState.zoom = Number.parseFloat(adminPromoZoom.value) || 1;
            var clamped = clampAdminPromoCropOffsets(adminPromoCropState.offsetX, adminPromoCropState.offsetY);
            adminPromoCropState.offsetX = clamped.x;
            adminPromoCropState.offsetY = clamped.y;
            syncAdminPromoPreviewTransform();
        });
    }

    if (adminPromoBrowse && adminPromoFileInput) {
        adminPromoBrowse.addEventListener("click", function () {
            adminPromoFileInput.click();
        });
    }

    if (adminPromoRecrop && adminPromoPreviewImage) {
        adminPromoRecrop.addEventListener("click", function () {
            if (adminPromoPreviewImage.hidden || !adminPromoPreviewImage.src) {
                return;
            }

            adminPromoCropState.previewBeforeCrop = adminPromoPreviewImage.src;
            adminPromoCropState.sourceImage = adminPromoPreviewImage.src;
            resetAdminPromoCropState();
            setAdminPromoCropWorkspaceVisible(true);
            syncAdminPromoPreviewTransform();
        });
    }

    if (adminPromoFileInput && adminPromoPreviewImage) {
        adminPromoPreviewImage.addEventListener("dragstart", function (event) {
            event.preventDefault();
        });

        adminPromoFileInput.addEventListener("change", function () {
            var file = adminPromoFileInput.files && adminPromoFileInput.files[0] ? adminPromoFileInput.files[0] : null;

            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (loadEvent) {
                adminPromoCropState.previewBeforeCrop = adminPromoPreviewImage.hidden ? "" : (adminPromoPreviewImage.src || "");
                adminPromoCropState.sourceImage = String(loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");
                setAdminPromoPreviewSource(adminPromoCropState.sourceImage);
                updateAdminPromoRecropState();
                resetAdminPromoCropState();
                setAdminPromoCropWorkspaceVisible(true);
                syncAdminPromoPreviewTransform();
            };
            reader.readAsDataURL(file);
        });
    }

    if (adminPromoPreviewWrap) {
        adminPromoPreviewWrap.addEventListener("wheel", function (event) {
            if (!adminPromoCropState.isCropping || !adminPromoZoom) {
                return;
            }

            event.preventDefault();

            var minZoom = Number.parseFloat(adminPromoZoom.min || "1");
            var maxZoom = Number.parseFloat(adminPromoZoom.max || "3");
            var stepZoom = Number.parseFloat(adminPromoZoom.step || "0.01");
            var direction = event.deltaY < 0 ? 1 : -1;
            var nextZoom = adminPromoCropState.zoom + (direction * (stepZoom * 5));

            nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
            nextZoom = Math.round(nextZoom * 100) / 100;

            adminPromoCropState.zoom = nextZoom;
            adminPromoZoom.value = String(nextZoom);

            var clamped = clampAdminPromoCropOffsets(adminPromoCropState.offsetX, adminPromoCropState.offsetY);
            adminPromoCropState.offsetX = clamped.x;
            adminPromoCropState.offsetY = clamped.y;
            syncAdminPromoPreviewTransform();
        }, { passive: false });

        adminPromoPreviewWrap.addEventListener("pointerdown", function (event) {
            if (!adminPromoCropState.isCropping || event.button !== 0) {
                return;
            }

            event.preventDefault();

            adminPromoCropState.isDragging = true;
            adminPromoCropState.dragPointerId = event.pointerId;
            adminPromoCropState.dragStartClientX = event.clientX;
            adminPromoCropState.dragStartClientY = event.clientY;
            adminPromoCropState.dragStartOffsetX = adminPromoCropState.offsetX;
            adminPromoCropState.dragStartOffsetY = adminPromoCropState.offsetY;
            adminPromoPreviewWrap.setPointerCapture(event.pointerId);
        });

        adminPromoPreviewWrap.addEventListener("pointermove", function (event) {
            if (!adminPromoCropState.isCropping || !adminPromoCropState.isDragging || adminPromoCropState.dragPointerId !== event.pointerId) {
                return;
            }

            var nextX = adminPromoCropState.dragStartOffsetX + (event.clientX - adminPromoCropState.dragStartClientX);
            var nextY = adminPromoCropState.dragStartOffsetY + (event.clientY - adminPromoCropState.dragStartClientY);
            var clamped = clampAdminPromoCropOffsets(nextX, nextY);
            adminPromoCropState.offsetX = clamped.x;
            adminPromoCropState.offsetY = clamped.y;
            syncAdminPromoPreviewTransform();
        });

        function stopAdminPromoCropDrag(event) {
            if (!adminPromoCropState.isDragging || adminPromoCropState.dragPointerId !== event.pointerId) {
                return;
            }

            adminPromoCropState.isDragging = false;
            adminPromoCropState.dragPointerId = null;
            adminPromoPreviewWrap.releasePointerCapture(event.pointerId);
        }

        adminPromoPreviewWrap.addEventListener("pointerup", stopAdminPromoCropDrag);
        adminPromoPreviewWrap.addEventListener("pointercancel", stopAdminPromoCropDrag);
    }

    if (adminPromoCropCancel && adminPromoPreviewImage) {
        adminPromoCropCancel.addEventListener("click", function () {
            setAdminPromoPreviewSource(adminPromoCropState.previewBeforeCrop || "");
            updateAdminPromoRecropState();
            adminPromoCropState.sourceImage = "";
            resetAdminPromoCropState();
            setAdminPromoCropWorkspaceVisible(false);
        });
    }

    if (adminPromoCropSave && adminPromoPreviewImage) {
        adminPromoCropSave.addEventListener("click", function () {
            var croppedDataUrl = buildAdminPromoCropDataUrlFromPreview();

            if (!croppedDataUrl) {
                return;
            }

            setAdminPromoPreviewSource(croppedDataUrl);
            adminPromoCropState.previewBeforeCrop = croppedDataUrl;
            adminPromoCropState.sourceImage = "";
            updateAdminPromoRecropState();
            resetAdminPromoCropState();
            setAdminPromoCropWorkspaceVisible(false);
        });
    }

    if (adminPromoClose) {
        adminPromoClose.addEventListener("click", closeAdminPromoEditModal);
    }

    if (adminPromoCancel) {
        adminPromoCancel.addEventListener("click", closeAdminPromoEditModal);
    }

    if (adminPromoEditBackdrop) {
        adminPromoEditBackdrop.addEventListener("click", function (event) {
            if (event.target === adminPromoEditBackdrop) {
                closeAdminPromoEditModal();
            }
        });
    }

    if (adminPromoForm) {
        adminPromoForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (!activeAdminPromoSlot || !adminPromoUpdateEndpoint) {
                return;
            }

            var finalPreviewSrc = adminPromoPreviewImage && !adminPromoPreviewImage.hidden ? adminPromoPreviewImage.src : "";

            if (adminPromoCropState.isCropping) {
                var autoCroppedDataUrl = buildAdminPromoCropDataUrlFromPreview();

                if (autoCroppedDataUrl) {
                    finalPreviewSrc = autoCroppedDataUrl;
                    setAdminPromoPreviewSource(autoCroppedDataUrl);
                    adminPromoCropState.previewBeforeCrop = autoCroppedDataUrl;
                }

                adminPromoCropState.sourceImage = "";
                resetAdminPromoCropState();
                setAdminPromoCropWorkspaceVisible(false);
            }

            if (!finalPreviewSrc) {
                window.alert("Please add an image first.");
                return;
            }

            var submitButton = adminPromoForm.querySelector('button[type="submit"]');
            var previousSubmitTitle = submitButton ? submitButton.getAttribute("title") : "";

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.setAttribute("title", "Saving...");
            }

            fetch(adminPromoUpdateEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    slot: Number.parseInt(activeAdminPromoSlot, 10),
                    imageDataUrl: finalPreviewSrc
                })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : "Unable to save promo banner.";
                        throw new Error(message);
                    }

                    var savedSlot = result.payload && result.payload.slot ? String(result.payload.slot) : activeAdminPromoSlot;
                    var savedFilename = buildPromoSlotFilename(savedSlot);
                    var savedSource = adminPromoImageBase + savedFilename + "?t=" + String(Date.now());
                    var existingSlide = promoBanner ? promoBanner.querySelector('.promo-slide[data-promo-slot="' + savedSlot + '"]') : null;
                    var savedSlide = existingSlide;

                    if (!savedSlide) {
                        savedSlide = document.createElement("div");
                        savedSlide.className = "promo-slide " + buildPromoSlideClass(savedSlot);
                        savedSlide.setAttribute("data-promo-slot", savedSlot);
                        savedSlide.innerHTML = '<img class="promo-image" src="' + savedSource + '" alt="Promo banner slot ' + savedSlot + '">';
                        insertPromoSlideSorted(savedSlide);
                    } else {
                        var image = savedSlide.querySelector(".promo-image");

                        if (!image) {
                            savedSlide.innerHTML = '<img class="promo-image" src="' + savedSource + '" alt="Promo banner slot ' + savedSlot + '">';
                        } else {
                            image.src = savedSource;
                            image.alt = "Promo banner slot " + savedSlot;
                        }
                    }

                    updatePromoAddSlideSlot();

                    var slidesAfterSave = Array.prototype.slice.call(getPromoSlides());
                    var savedIndex = slidesAfterSave.indexOf(savedSlide);

                    updatePromoControls();
                    showPromoSlide(savedIndex >= 0 ? savedIndex : 0);
                    stopPromoTimer();
                    startPromoTimer();

                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.setAttribute("title", previousSubmitTitle || "Save Changes");
                    }

                    closeAdminPromoEditModal();
                })
                .catch(function (error) {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.setAttribute("title", previousSubmitTitle || "Save Changes");
                    }

                    window.alert(error.message || "Unable to save promo banner.");
                });
        });
    }

    function closeFilterPanels(exceptId) {
        filterToggles.forEach(function (toggle) {
            var panelId = toggle.getAttribute("aria-controls");
            var panel = document.getElementById(panelId);
            var isOpen = panelId === exceptId;

            toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");

            if (panel) {
                panel.hidden = !isOpen;
            }
        });
    }

    function setAdminNavSwapState(nav, isSwapped) {
        var primaryItems = nav.querySelectorAll('[data-admin-nav-item="primary"]');
        var swappedItems = nav.querySelectorAll('[data-admin-nav-item="swapped"]');
        var swapButton = nav.querySelector("[data-admin-nav-swap]");

        nav.classList.toggle("is-swapped", isSwapped);

        primaryItems.forEach(function (item) {
            item.hidden = isSwapped;
        });

        swappedItems.forEach(function (item) {
            item.hidden = !isSwapped;
        });

        if (swapButton) {
            swapButton.setAttribute("aria-pressed", isSwapped ? "true" : "false");
            swapButton.setAttribute("title", isSwapped ? "Show filters bar" : "Show management bar");
        }
    }

    function applyProductFilters() {
        if (!productCards.length && !catalogFilterForm) {
            return;
        }

        var effectiveToday = getEffectiveToday();
        var visibleCount = 0;

        productCards.forEach(function (card) {
            if (card.getAttribute("data-admin-removed") === "true") {
                card.classList.add("is-hidden");
                return;
            }

            var cardBrand = normalizeFilterValue(card.getAttribute("data-brand"));
            var brandMatches = activeFilters.brand === "all" || activeFilters.brand === cardBrand;
            var hasDateFilter = activeFilters.month !== "all" || activeFilters.day !== "all" || activeFilters.year !== "all";
            var matches = brandMatches;

            if (matches && catalogFilterForm) {
                var cardSkills = String(card.getAttribute("data-skill-levels") || "").split(/\s+/);
                var cardCategory = String(card.getAttribute("data-category") || "");
                var cardPrice = Number(card.getAttribute("data-price"));
                var skillMatches = !catalogFilters.skills.length || catalogFilters.skills.some(function (skill) {
                    return cardSkills.includes(skill);
                });
                var categoryMatches = !catalogFilters.categories.length || catalogFilters.categories.includes(cardCategory);
                matches = skillMatches && categoryMatches && Number.isFinite(cardPrice)
                    && cardPrice >= catalogFilters.min && cardPrice <= catalogFilters.max;
            }

            if (matches && hasDateFilter) {
                var selectedYear = activeFilters.year === "all" ? calendarViewYear : Number.parseInt(activeFilters.year, 10);
                var selectedMonthIndex = activeFilters.month === "all" ? calendarViewMonthIndex : monthValueToIndex[activeFilters.month];
                var selectedDay = activeFilters.day === "all" ? null : Number.parseInt(activeFilters.day, 10);

                if (!Number.isFinite(selectedYear)) {
                    matches = false;
                } else if (selectedDay !== null && Number.isFinite(selectedYear) && Number.isFinite(selectedMonthIndex)) {
                    var targetDate = new Date(selectedYear, selectedMonthIndex, selectedDay);
                    matches = targetDate >= effectiveToday;
                } else if (activeFilters.month !== "all" && Number.isFinite(selectedYear) && Number.isFinite(selectedMonthIndex)) {
                    matches = selectedYear > effectiveToday.getFullYear() || (selectedYear === effectiveToday.getFullYear() && selectedMonthIndex >= effectiveToday.getMonth());
                } else if (activeFilters.year !== "all" && Number.isFinite(selectedYear)) {
                    matches = selectedYear >= effectiveToday.getFullYear();
                }
            }

            card.classList.toggle("is-hidden", !matches);

            if (matches) {
                visibleCount += 1;
            }
        });

        if (productEmpty) {
            productEmpty.hidden = visibleCount !== 0;
        }

        if (catalogFilterForm) {
            var activeCount = catalogFilters.skills.length + catalogFilters.categories.length
                + Number(catalogFilters.min > 0) + Number(catalogFilters.max < catalogPriceLimit);
            Object.keys(activeFilters).forEach(function (group) {
                activeCount += Number(activeFilters[group] !== "all");
            });
            catalogFilterCount.textContent = String(activeCount);
            catalogFilterCount.hidden = activeCount === 0;
            var catalogToggle = document.querySelector('[aria-controls="catalog-filter-panel"]');
            catalogToggle.setAttribute("aria-label", activeCount ? "Filters, " + activeCount + " active" : "Filters");
            catalogResultsCount.textContent = visibleCount + " of " + productCards.length + " products";
            catalogViewResults.textContent = visibleCount ? "View " + visibleCount + (visibleCount === 1 ? " product" : " products") : "View results";
            catalogResetButtons.forEach(function (button) {
                if (button.classList.contains("catalog-empty-reset")) {
                    button.hidden = visibleCount !== 0 || activeCount === 0;
                } else {
                    button.disabled = activeCount === 0;
                }
            });
        }
    }

    function updateCatalogPrice(bound, value) {
        var parsed = Number(value);
        if (!Number.isFinite(parsed) || String(value).trim() === "") {
            parsed = bound === "min" ? 0 : catalogPriceLimit;
        }
        catalogFilters[bound] = Math.round(Math.min(catalogPriceLimit, Math.max(0, parsed)) * 100) / 100;
        if (catalogFilters.min > catalogFilters.max) {
            catalogFilters[bound === "min" ? "max" : "min"] = catalogFilters[bound];
        }
        ["min", "max"].forEach(function (edge) {
            catalogFilterForm.querySelector('[data-catalog-price="' + edge + '"]').value = String(catalogFilters[edge]);
            catalogFilterForm.querySelector('[data-catalog-range="' + edge + '"]').value = String(catalogFilters[edge]);
        });
        applyProductFilters();
    }

    if (catalogFilterForm) {
        catalogFilterForm.addEventListener("change", function (event) {
            var target = event.target;
            if (target.hasAttribute("data-catalog-price")) {
                updateCatalogPrice(target.getAttribute("data-catalog-price"), target.value);
                return;
            }
            catalogFilters.skills = Array.from(catalogFilterForm.querySelectorAll("[data-catalog-skill]:checked"), function (input) { return input.value; });
            catalogFilters.categories = Array.from(catalogFilterForm.querySelectorAll("[data-catalog-category]:checked"), function (input) { return input.value; });
            applyProductFilters();
        });
        catalogFilterForm.addEventListener("input", function (event) {
            var target = event.target;
            if (target.hasAttribute("data-catalog-range")) {
                updateCatalogPrice(target.getAttribute("data-catalog-range"), target.value);
            } else if (target.hasAttribute("data-catalog-price") && target.value !== "") {
                var bound = target.getAttribute("data-catalog-price");
                var value = Number(target.value);
                if (Number.isFinite(value) && value >= 0 && value <= catalogPriceLimit
                    && (bound === "min" ? value <= catalogFilters.max : value >= catalogFilters.min)) {
                    catalogFilters[bound] = value;
                    catalogFilterForm.querySelector('[data-catalog-range="' + bound + '"]').value = String(value);
                    applyProductFilters();
                }
            }
        });
        catalogResetButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                catalogFilterForm.reset();
                catalogFilters = { skills: [], categories: [], min: 0, max: catalogPriceLimit };
                Object.keys(activeFilters).forEach(function (group) {
                    activeFilters[group] = "all";
                    syncFilterButtons(group);
                });
                refreshDatePickerUI();
                applyProductFilters();
            });
        });
        catalogFilterForm.addEventListener("submit", function (event) {
            event.preventDefault();
            var title = document.getElementById("featured-products-title");
            closeFilterPanels(null);
            title.focus({ preventScroll: true });
            title.scrollIntoView({ block: "start", behavior: "auto" });
        });
    }

    function initializeAdminBusinessReportPrint() {
        if (!adminBusinessReportPrintButtons.length || !adminBusinessReportPayload) {
            return;
        }

        function formatReportCurrency(value) {
            var parsed = Number(value);
            if (!Number.isFinite(parsed)) {
                parsed = 0;
            }

            return "PHP " + parsed.toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function buildBusinessReportPdf() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                window.alert("PDF library is unavailable right now.");
                return;
            }

            var jsPDF = window.jspdf.jsPDF;
            var doc = new jsPDF({
                orientation: "portrait",
                unit: "pt",
                format: "a4"
            });

            var pageWidth = doc.internal.pageSize.getWidth();
            var marginX = 42;
            var y = 56;
            var pageHeight = doc.internal.pageSize.getHeight();
            var timestamp = String(adminBusinessReportPayload.generatedAt || "");
            var reportYear = Number(adminBusinessReportPayload.year || new Date().getFullYear());
            var summary = adminBusinessReportPayload.summary && typeof adminBusinessReportPayload.summary === "object"
                ? adminBusinessReportPayload.summary
                : {};

            function ensureVerticalSpace(requiredHeight) {
                if ((y + requiredHeight) <= (pageHeight - 44)) {
                    return;
                }

                doc.addPage();
                y = 56;
            }

            function sectionTitle(titleText) {
                ensureVerticalSpace(34);
                doc.setFont("helvetica", "bold");
                doc.setFontSize(13);
                doc.setTextColor(16, 18, 20);
                doc.text(titleText, marginX, y);
                y += 16;
            }

            doc.setTextColor(16, 18, 20);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(24);
            doc.text("THE NIFTY FIFTY", pageWidth / 2, y, { align: "center" });
            y += 20;

            doc.setFont("helvetica", "normal");
            doc.setFontSize(11);
            doc.setTextColor(71, 75, 84);
            doc.text("Business Performance Report (" + String(reportYear) + ")", pageWidth / 2, y, { align: "center" });
            y += 16;
            doc.text("Generated: " + (timestamp || "-"), pageWidth / 2, y, { align: "center" });
            y += 26;

            doc.setDrawColor(210, 214, 222);
            doc.line(marginX, y, pageWidth - marginX, y);
            y += 24;

            sectionTitle("Executive Summary");

            doc.setFont("helvetica", "normal");
            doc.setFontSize(11);
            doc.setTextColor(50, 53, 60);
            doc.text("Transactions: " + String(Number(summary.transactions || 0)), marginX, y);
            y += 14;
            doc.text("Revenue: " + formatReportCurrency(summary.revenue || 0), marginX, y);
            y += 26;

            var monthlyRows = Array.isArray(adminBusinessReportPayload.monthlyRevenueRows)
                ? adminBusinessReportPayload.monthlyRevenueRows
                : [];

            if (typeof doc.autoTable === "function") {
                ensureVerticalSpace(120);
                doc.autoTable({
                    startY: y,
                    head: [["Month", "Transactions", "Revenue"]],
                    body: monthlyRows.map(function (row) {
                        return [
                            String(row.month || "-"),
                            String(Number(row.transactions || 0)),
                            formatReportCurrency(row.revenue || 0)
                        ];
                    }),
                    theme: "grid",
                    headStyles: { fillColor: [17, 18, 20], textColor: 245, fontSize: 10 },
                    bodyStyles: { fontSize: 9, textColor: [28, 30, 34] },
                    styles: { cellPadding: { top: 7, right: 7, bottom: 7, left: 7 } },
                    margin: { left: marginX, right: marginX }
                });
                y = doc.lastAutoTable.finalY + 30;
            }

            sectionTitle("Equipment Inventory and Status");

            var equipmentRows = Array.isArray(adminBusinessReportPayload.equipmentInventoryRows)
                ? adminBusinessReportPayload.equipmentInventoryRows
                : [];

            if (typeof doc.autoTable === "function") {
                ensureVerticalSpace(140);
                doc.autoTable({
                    startY: y,
                    head: [["Equipment", "Available", "Maintenance", "Unavailable", "Total"]],
                    body: equipmentRows.map(function (row) {
                        return [
                            String(row.name || "Equipment"),
                            String(Number(row.available || 0)),
                            String(Number(row.maintenance || 0)),
                            String(Number(row.unavailable || 0)),
                            String(Number(row.total || 0))
                        ];
                    }),
                    theme: "grid",
                    headStyles: { fillColor: [17, 18, 20], textColor: 245, fontSize: 10 },
                    bodyStyles: { fontSize: 9, textColor: [28, 30, 34] },
                    styles: { cellPadding: { top: 7, right: 7, bottom: 7, left: 7 } },
                    margin: { left: marginX, right: marginX }
                });
                y = doc.lastAutoTable.finalY + 32;
            }

            var damagedRows = Array.isArray(adminBusinessReportPayload.damagedEquipmentRows)
                ? adminBusinessReportPayload.damagedEquipmentRows
                : [];

            sectionTitle("Maintenance / Damage Unit List");

            if (typeof doc.autoTable === "function") {
                ensureVerticalSpace(120);
                doc.autoTable({
                    startY: y,
                    head: [["Equipment", "Unit ID", "Status"]],
                    body: (damagedRows.length ? damagedRows : [{ name: "No damaged equipment reported.", unitId: "-", status: "-" }]).map(function (row) {
                        return [
                            String(row.name || "-"),
                            String(row.unitId || "-"),
                            String(row.status || "-")
                        ];
                    }),
                    theme: "grid",
                    headStyles: { fillColor: [17, 18, 20], textColor: 245, fontSize: 10 },
                    bodyStyles: { fontSize: 9, textColor: [28, 30, 34] },
                    styles: { cellPadding: { top: 7, right: 7, bottom: 7, left: 7 } },
                    margin: { left: marginX, right: marginX }
                });
                y = doc.lastAutoTable.finalY + 32;
            }

            var servicesRows = Array.isArray(adminBusinessReportPayload.servicesRevenueRows)
                ? adminBusinessReportPayload.servicesRevenueRows
                : [];

            sectionTitle("Services Revenue Report");

            if (typeof doc.autoTable === "function") {
                ensureVerticalSpace(140);
                doc.autoTable({
                    startY: y,
                    head: [["Service Type", "Package", "Bookings", "Revenue"]],
                    body: (function () {
                        var rows = [];

                        servicesRows.forEach(function (serviceGroup) {
                            var typeLabel = String(serviceGroup.typeLabel || "Service");
                            var packages = Array.isArray(serviceGroup.packages) ? serviceGroup.packages : [];

                            if (!packages.length) {
                                rows.push([typeLabel, "-", "0", formatReportCurrency(serviceGroup.totalRevenue || 0)]);
                                return;
                            }

                            packages.forEach(function (packageRow, index) {
                                rows.push([
                                    index === 0 ? typeLabel : "",
                                    String(packageRow.title || "Service Package"),
                                    String(Number(packageRow.bookings || 0)),
                                    formatReportCurrency(packageRow.revenue || 0)
                                ]);
                            });

                            rows.push(["", "Type Total", "", formatReportCurrency(serviceGroup.totalRevenue || 0)]);
                        });

                        if (!rows.length) {
                            rows.push(["No service revenue rows available.", "-", "-", formatReportCurrency(0)]);
                        }

                        return rows;
                    })(),
                    theme: "grid",
                    headStyles: { fillColor: [17, 18, 20], textColor: 245, fontSize: 10 },
                    bodyStyles: { fontSize: 9, textColor: [28, 30, 34] },
                    styles: { cellPadding: { top: 7, right: 7, bottom: 7, left: 7 } },
                    margin: { left: marginX, right: marginX }
                });
                y = doc.lastAutoTable.finalY + 24;
            }

            var filename = "nifty-fifty-business-report-" + String(reportYear) + ".pdf";
            doc.save(filename);
        }

        adminBusinessReportPrintButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                buildBusinessReportPdf();
            });
        });
    }

    function getAdminDashboardVisibilityStorageKey() {
        return "creaty.adminDashboardVisibility.v2";
    }

    function getAdminDashboardSectionDefaults() {
        var defaults = {};

        adminDashboardSections.forEach(function (section) {
            var key = String(section.getAttribute("data-admin-dashboard-section") || "").trim();

            if (!key) {
                return;
            }

            defaults[key] = section.getAttribute("data-admin-dashboard-section-default-visible") !== "false";
        });

        return defaults;
    }

    function getAdminDashboardSectionMetaByKey() {
        var sectionMap = {};

        adminDashboardSections.forEach(function (section) {
            var key = String(section.getAttribute("data-admin-dashboard-section") || "").trim();

            if (!key || sectionMap[key]) {
                return;
            }

            sectionMap[key] = {
                label: String(section.getAttribute("data-admin-dashboard-section-label") || key).trim(),
                isDefaultVisible: section.getAttribute("data-admin-dashboard-section-default-visible") !== "false"
            };
        });

        return sectionMap;
    }

    function loadAdminDashboardVisibility() {
        var defaults = getAdminDashboardSectionDefaults();

        try {
            var storedValue = window.localStorage.getItem(getAdminDashboardVisibilityStorageKey());

            if (!storedValue) {
                return defaults;
            }

            var parsed = JSON.parse(storedValue);

            if (!parsed || typeof parsed !== "object") {
                return defaults;
            }

            Object.keys(defaults).forEach(function (key) {
                if (typeof parsed[key] === "boolean") {
                    defaults[key] = parsed[key];
                }
            });
        } catch (error) {
            return defaults;
        }

        return defaults;
    }

    function saveAdminDashboardVisibility(settings) {
        try {
            window.localStorage.setItem(getAdminDashboardVisibilityStorageKey(), JSON.stringify(settings || {}));
        } catch (error) {
            // Ignore storage failures and keep current visibility state.
        }
    }

    function applyAdminDashboardVisibility(settings) {
        var effectiveSettings = settings && typeof settings === "object"
            ? settings
            : getAdminDashboardSectionDefaults();

        adminDashboardSections.forEach(function (section) {
            var key = String(section.getAttribute("data-admin-dashboard-section") || "").trim();

            if (!key) {
                return;
            }

            section.classList.toggle("admin-dashboard-section-hidden", effectiveSettings[key] === false);
        });
    }

    function buildAdminDashboardSettingsList() {
        if (!adminDashboardSettingsList) {
            return;
        }

        var sectionMap = getAdminDashboardSectionMetaByKey();

        adminDashboardSettingsList.innerHTML = "";

        Object.keys(sectionMap).forEach(function (key) {
            var meta = sectionMap[key];
            var label = String(meta.label || key).trim();

            if (!key || !label) {
                return;
            }

            var option = document.createElement("label");
            option.className = "admin-dashboard-settings-option";

            var copy = document.createElement("div");
            var title = document.createElement("strong");
            title.textContent = label;
            copy.appendChild(title);

            var note = document.createElement("span");
            note.textContent = key === "operations"
                ? "Instructor-focused operations board"
                : "Dashboard visibility";
            copy.appendChild(note);

            var checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = meta.isDefaultVisible !== false;
            checkbox.setAttribute("data-admin-dashboard-settings-key", key);

            option.appendChild(copy);
            option.appendChild(checkbox);
            adminDashboardSettingsList.appendChild(option);
        });
    }

    function syncAdminDashboardSettingsInputs(settings) {
        if (!adminDashboardSettingsList) {
            return;
        }

        var effectiveSettings = settings && typeof settings === "object"
            ? settings
            : getAdminDashboardSectionDefaults();

        adminDashboardSettingsList.querySelectorAll("[data-admin-dashboard-settings-key]").forEach(function (input) {
            var key = String(input.getAttribute("data-admin-dashboard-settings-key") || "").trim();
            input.checked = effectiveSettings[key] !== false;
        });
    }

    function collectAdminDashboardVisibilityFromInputs() {
        var nextSettings = getAdminDashboardSectionDefaults();

        if (!adminDashboardSettingsList) {
            return nextSettings;
        }

        adminDashboardSettingsList.querySelectorAll("[data-admin-dashboard-settings-key]").forEach(function (input) {
            var key = String(input.getAttribute("data-admin-dashboard-settings-key") || "").trim();

            if (!key) {
                return;
            }

            nextSettings[key] = Boolean(input.checked);
        });

        return nextSettings;
    }

    function openAdminDashboardSettings() {
        if (!adminDashboardSettingsBackdrop) {
            return;
        }

        syncAdminDashboardSettingsInputs(loadAdminDashboardVisibility());
        adminDashboardSettingsBackdrop.hidden = false;
    }

    function closeAdminDashboardSettings() {
        if (!adminDashboardSettingsBackdrop) {
            return;
        }

        adminDashboardSettingsBackdrop.hidden = true;
    }

    if (adminDashboardSections.length) {
        buildAdminDashboardSettingsList();
        applyAdminDashboardVisibility(loadAdminDashboardVisibility());
    }

    adminDashboardSettingsOpenButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openAdminDashboardSettings();
        });
    });

    adminDashboardSettingsCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminDashboardSettings();
        });
    });

    if (adminDashboardSettingsList) {
        adminDashboardSettingsList.addEventListener("change", function () {
            var nextSettings = collectAdminDashboardVisibilityFromInputs();
            applyAdminDashboardVisibility(nextSettings);
            saveAdminDashboardVisibility(nextSettings);
        });
    }

    if (adminDashboardSettingsResetButton) {
        adminDashboardSettingsResetButton.addEventListener("click", function () {
            var defaults = getAdminDashboardSectionDefaults();
            applyAdminDashboardVisibility(defaults);
            syncAdminDashboardSettingsInputs(defaults);
            saveAdminDashboardVisibility(defaults);
        });
    }

    if (adminDashboardSettingsBackdrop) {
        adminDashboardSettingsBackdrop.addEventListener("click", function (event) {
            if (event.target === adminDashboardSettingsBackdrop) {
                closeAdminDashboardSettings();
            }
        });
    }

    function padTwo(value) {
        return String(value).padStart(2, "0");
    }

    function getStartOfDay(dateValue) {
        var date = new Date(dateValue.getFullYear(), dateValue.getMonth(), dateValue.getDate());
        date.setHours(0, 0, 0, 0);
        return date;
    }

    function getEffectiveToday() {
        return getStartOfDay(new Date());
    }

    function dateKeyFromDate(dateValue) {
        return String(dateValue.getFullYear()) + "-" + padTwo(dateValue.getMonth() + 1) + "-" + padTwo(dateValue.getDate());
    }

    function extractProductKeyFromHref(hrefValue) {
        if (!hrefValue) {
            return "";
        }

        var productMatch = hrefValue.match(/[?&]product=([^&#]+)/i);

        if (!productMatch || !productMatch[1]) {
            return "";
        }

        try {
            return decodeURIComponent(productMatch[1]).toLowerCase();
        } catch (error) {
            return String(productMatch[1]).toLowerCase();
        }
    }

    function getCardProductKey(card) {
        if (!card) {
            return "";
        }

        var existingKey = normalizeFilterValue(card.getAttribute("data-product-key"));

        if (existingKey && existingKey !== "all") {
            return existingKey;
        }

        var productLink = card.querySelector(".product-visual-link") || card.querySelector(".product-title-link");
        var parsedKey = extractProductKeyFromHref(productLink ? productLink.getAttribute("href") : "");

        if (parsedKey) {
            card.setAttribute("data-product-key", parsedKey);
        }

        return parsedKey;
    }

    function collectAvailableYearsFromCards() {
        var years = new Set();
        var baseYear = getEffectiveToday().getFullYear();

        years.add(baseYear);
        years.add(baseYear + 1);
        years.add(baseYear + 2);

        return Array.from(years).sort(function (left, right) {
            return left - right;
        });
    }

    function syncCalendarViewFromFilters() {
        var effectiveToday = getEffectiveToday();
        var yearPool = availableYears.length ? availableYears : collectAvailableYearsFromCards();
        var minYear = yearPool[0];
        var maxYear = yearPool[yearPool.length - 1];

        if (activeFilters.year !== "all") {
            var parsedYear = Number.parseInt(activeFilters.year, 10);

            if (Number.isFinite(parsedYear)) {
                calendarViewYear = parsedYear;
            }
        } else if (!Number.isFinite(calendarViewYear)) {
            calendarViewYear = effectiveToday.getFullYear();
        }

        calendarViewYear = Math.min(Math.max(calendarViewYear, minYear), maxYear);

        if (activeFilters.month !== "all" && monthValueToIndex[activeFilters.month] !== undefined) {
            calendarViewMonthIndex = monthValueToIndex[activeFilters.month];
        } else if (!Number.isFinite(calendarViewMonthIndex) || calendarViewMonthIndex < 0 || calendarViewMonthIndex > 11) {
            calendarViewMonthIndex = calendarViewYear === effectiveToday.getFullYear() ? effectiveToday.getMonth() : 0;
        }
    }

    function normalizeFilterValue(value) {
        return String(value || "all").toLowerCase();
    }

    function getMonthLabel(monthValue) {
        var normalized = normalizeFilterValue(monthValue);
        var month = monthDefinitions.find(function (item) {
            return item.value === normalized;
        });

        return month ? month.label : "Month";
    }

    function syncFilterButtons(group) {
        if (!filterNav) {
            return;
        }

        var selected = activeFilters[group];

        filterNav.querySelectorAll('.filter-option[data-filter-group="' + group + '"]').forEach(function (option) {
            var optionValue = normalizeFilterValue(option.getAttribute("data-filter-value"));
            option.classList.toggle("is-selected", optionValue === selected);
        });
    }

    function setActiveFilter(group, value, options) {
        var config = options || {};
        var normalizedValue = normalizeFilterValue(value);

        activeFilters[group] = normalizedValue;
        syncFilterButtons(group);

        if (!config.skipApply) {
            applyProductFilters();
        }
    }

    function buildDateAvailability() {
        var effectiveToday = getEffectiveToday();
        var daysInVisibleMonth = new Date(calendarViewYear, calendarViewMonthIndex + 1, 0).getDate();
        availableDateKeys.clear();

        for (var day = 1; day <= daysInVisibleMonth; day += 1) {
            var candidateDate = new Date(calendarViewYear, calendarViewMonthIndex, day);

            if (candidateDate >= effectiveToday) {
                availableDateKeys.add(dateKeyFromDate(candidateDate));
            }
        }

        availableYears = collectAvailableYearsFromCards();

        if (!Number.isFinite(calendarViewYear)) {
            calendarViewYear = effectiveToday.getFullYear();
        }

        calendarViewYear = Math.min(Math.max(calendarViewYear, availableYears[0]), availableYears[availableYears.length - 1]);

        if (!Number.isFinite(calendarViewMonthIndex) || calendarViewMonthIndex < 0 || calendarViewMonthIndex > 11) {
            calendarViewMonthIndex = calendarViewYear === effectiveToday.getFullYear() ? effectiveToday.getMonth() : 0;
        }
    }

    function setActiveDateView(nextView) {
        if (!dateTabButtons.length || !dateViews.length) {
            return;
        }

        dateTabButtons.forEach(function (button) {
            var isSelected = button.getAttribute("data-date-tab") === nextView;
            button.classList.toggle("is-active", isSelected);
            button.setAttribute("aria-selected", isSelected ? "true" : "false");
        });

        dateViews.forEach(function (view) {
            view.classList.toggle("is-active", view.getAttribute("data-date-view") === nextView);
        });

        if (nextView === "day") {
            renderCalendarView();
        }
    }

    function updateDateTabLabels() {
        if (!dateTabButtons.length) {
            return;
        }

        dateTabButtons.forEach(function (button) {
            var tab = button.getAttribute("data-date-tab");
            button.textContent = tab ? tab.charAt(0).toUpperCase() + tab.slice(1) : "Date";
        });
    }

    function createDateOptionButton(group, value, label) {
        var button = document.createElement("button");
        var normalizedValue = normalizeFilterValue(value);

        button.type = "button";
        button.className = "filter-option";
        button.setAttribute("data-filter-group", group);
        button.setAttribute("data-filter-value", normalizedValue);
        button.textContent = label;
        button.classList.toggle("is-selected", activeFilters[group] === normalizedValue);

        return button;
    }

    function renderMonthOptions() {
        if (!monthOptionsWrap) {
            return;
        }

        var effectiveToday = getEffectiveToday();
        var selectedYear = activeFilters.year === "all" ? effectiveToday.getFullYear() : Number.parseInt(activeFilters.year, 10);

        monthOptionsWrap.innerHTML = "";
        monthOptionsWrap.appendChild(createDateOptionButton("month", "all", "All Months"));

        monthDefinitions.forEach(function (month, monthIndex) {
            var option = createDateOptionButton("month", month.value, month.label);
            var isPast = selectedYear < effectiveToday.getFullYear() || (selectedYear === effectiveToday.getFullYear() && monthIndex < effectiveToday.getMonth());

            if (isPast) {
                option.classList.add("is-past");
                option.disabled = true;
                option.setAttribute("aria-disabled", "true");
            }

            monthOptionsWrap.appendChild(option);
        });
    }

    function renderYearOptions() {
        if (!yearOptionsWrap) {
            return;
        }

        var effectiveToday = getEffectiveToday();

        yearOptionsWrap.innerHTML = "";
        yearOptionsWrap.appendChild(createDateOptionButton("year", "all", "All Years"));

        availableYears.forEach(function (yearValue) {
            var option = createDateOptionButton("year", String(yearValue), String(yearValue));

            if (yearValue < effectiveToday.getFullYear()) {
                option.classList.add("is-past");
                option.disabled = true;
                option.setAttribute("aria-disabled", "true");
            }

            yearOptionsWrap.appendChild(option);
        });
    }

    function renderCalendarView() {
        if (!calendarGrid) {
            return;
        }

        if (activeFilters.month !== "all" && monthValueToIndex[activeFilters.month] !== undefined) {
            calendarViewMonthIndex = monthValueToIndex[activeFilters.month];
        }

        if (activeFilters.year !== "all") {
            var parsedYear = Number.parseInt(activeFilters.year, 10);

            if (Number.isFinite(parsedYear)) {
                calendarViewYear = parsedYear;
            }
        }

        var monthLabel = monthDefinitions[calendarViewMonthIndex].label;
        var firstWeekday = new Date(calendarViewYear, calendarViewMonthIndex, 1).getDay();
        var totalDays = new Date(calendarViewYear, calendarViewMonthIndex + 1, 0).getDate();
        var effectiveToday = getEffectiveToday();

        calendarGrid.innerHTML = "";

        if (calendarTitle) {
            calendarTitle.textContent = monthLabel + " " + calendarViewYear;
        }

        for (var padIndex = 0; padIndex < firstWeekday; padIndex += 1) {
            var padCell = document.createElement("span");
            padCell.className = "date-calendar-cell is-empty";
            padCell.setAttribute("aria-hidden", "true");
            calendarGrid.appendChild(padCell);
        }

        for (var day = 1; day <= totalDays; day += 1) {
            var dayValue = padTwo(day);
            var monthValue = padTwo(calendarViewMonthIndex + 1);
            var dateKey = String(calendarViewYear) + "-" + monthValue + "-" + dayValue;
            var dayDate = new Date(calendarViewYear, calendarViewMonthIndex, day);
            var isPastDay = dayDate < effectiveToday;
            var isAvailable = availableDateKeys.has(dateKey);
            var dayButton = document.createElement("button");

            dayButton.type = "button";
            dayButton.className = "date-calendar-cell date-calendar-day";
            dayButton.textContent = String(day);
            dayButton.setAttribute("data-calendar-day", dayValue);
            dayButton.setAttribute("aria-label", monthLabel + " " + day + ", " + calendarViewYear);

            if (isPastDay) {
                dayButton.classList.add("is-past");
                dayButton.disabled = true;
            } else if (isAvailable) {
                dayButton.classList.add("is-available");
            } else {
                dayButton.classList.add("is-unavailable");
                dayButton.disabled = true;
            }

            if (
                activeFilters.day === dayValue &&
                activeFilters.month === monthDefinitions[calendarViewMonthIndex].value &&
                activeFilters.year === String(calendarViewYear)
            ) {
                dayButton.classList.add("is-selected");
            }

            calendarGrid.appendChild(dayButton);
        }

        syncFilterButtons("day");
    }

    function refreshDatePickerUI() {
        syncCalendarViewFromFilters();
        buildDateAvailability();
        renderMonthOptions();
        renderYearOptions();
        updateDateTabLabels();
        renderCalendarView();
    }

    function setupDetailGallery(gallery) {
        var slides = gallery.querySelectorAll(".detail-gallery-slide");
        var prevButton = gallery.querySelector('[data-gallery-direction="prev"]');
        var nextButton = gallery.querySelector('[data-gallery-direction="next"]');
        var currentIndex = 0;

        if (!slides.length) {
            gallery.classList.add("is-no-arrows");

            if (prevButton) {
                prevButton.hidden = true;
            }

            if (nextButton) {
                nextButton.hidden = true;
            }

            return;
        }

        if (slides.length === 1) {
            gallery.classList.add("is-no-arrows");

            if (prevButton) {
                prevButton.hidden = true;
            }

            if (nextButton) {
                nextButton.hidden = true;
            }
        } else {
            gallery.classList.remove("is-no-arrows");
        }

        function showSlide(nextIndex) {
            if (!slides.length) {
                return;
            }

            currentIndex = (nextIndex + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                var isActive = slideIndex === currentIndex;

                slide.classList.toggle("is-active", isActive);
                slide.setAttribute("aria-hidden", isActive ? "false" : "true");
            });
        }

        if (prevButton) {
            prevButton.addEventListener("click", function () {
                showSlide(currentIndex - 1);
            });
        }

        if (nextButton) {
            nextButton.addEventListener("click", function () {
                showSlide(currentIndex + 1);
            });
        }

        showSlide(0);
    }

    function setupPackageSlideshow(slideshow) {
        var slides = slideshow.querySelectorAll(".package-slide");
        var currentIndex = 0;
        var timer = null;
        var intervalBase = Number.parseInt(slideshow.getAttribute("data-autoplay-ms") || "6200", 10);

        if (!slides.length) {
            return null;
        }

        if (!Number.isFinite(intervalBase) || intervalBase < 2200) {
            intervalBase = 6200;
        }

        function getRandomIndexExcluding(current, total) {
            if (total < 2) {
                return current;
            }

            var next = current;

            while (next === current) {
                next = Math.floor(Math.random() * total);
            }

            return next;
        }

        function getRandomDelay() {
            var jitter = Math.floor(Math.random() * 1800);

            return intervalBase + jitter;
        }

        function showSlide(nextIndex) {
            currentIndex = (nextIndex + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                var isActive = slideIndex === currentIndex;

                slide.classList.toggle("is-active", isActive);
                slide.setAttribute("aria-hidden", isActive ? "false" : "true");

                if (String(slide && slide.tagName ? slide.tagName : "").toLowerCase() === "video") {
                    if (isActive) {
                        slide.muted = true;
                        slide.playsInline = true;

                        var playAttempt = slide.play();
                        if (playAttempt && typeof playAttempt.catch === "function") {
                            playAttempt.catch(function () {
                                // Ignore autoplay restrictions and keep slideshow working.
                            });
                        }
                    } else {
                        if (typeof slide.pause === "function") {
                            slide.pause();
                        }

                        try {
                            slide.currentTime = 0;
                        } catch (error) {
                            // Ignore media seek failures for unsupported sources.
                        }
                    }
                }
            });
        }

        function queueNext() {
            if (slides.length < 2) {
                return;
            }

            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                showSlide(getRandomIndexExcluding(currentIndex, slides.length));
                queueNext();
            }, getRandomDelay());
        }

        function start() {
            showSlide(currentIndex);

            if (slides.length >= 2) {
                queueNext();
            }
        }

        function stop() {
            window.clearTimeout(timer);
            timer = null;

            slides.forEach(function (slide) {
                if (String(slide && slide.tagName ? slide.tagName : "").toLowerCase() !== "video") {
                    return;
                }

                if (typeof slide.pause === "function") {
                    slide.pause();
                }
            });
        }

        showSlide(0);
        start();

        return {
            start: start,
            stop: stop
        };
    }

    updatePromoAddSlideSlot();
    updatePromoControls();

    if (promoBanner) {
        var promoSlides = getPromoSlides();

        if (promoSlides.length) {
            showPromoSlide(0);
            startPromoTimer();
        }

        if (promoPrev) {
            promoPrev.addEventListener("click", function () {
                stopPromoTimer();
                showPromoSlide(promoIndex - 1);
                startPromoTimer();
            });
        }

        if (promoNext) {
            promoNext.addEventListener("click", function () {
                stopPromoTimer();
                showPromoSlide(promoIndex + 1);
                startPromoTimer();
            });
        }

        document.addEventListener("visibilitychange", function () {
            if (document.hidden) {
                stopPromoTimer();
                return;
            }

            startPromoTimer();
        });
    }

    if (filterNav) {
        refreshDatePickerUI();

        filterToggles.forEach(function (toggle) {
            toggle.addEventListener("click", function () {
                var panelId = toggle.getAttribute("aria-controls");
                var expanded = toggle.getAttribute("aria-expanded") === "true";

                closeFilterPanels(expanded ? null : panelId);
            });
        });

        filterNav.addEventListener("click", function (event) {
            var target = event.target;
            var dateTabButton = target.closest("[data-date-tab]");

            if (dateTabButton && dateFilterPanel && dateFilterPanel.contains(dateTabButton)) {
                setActiveDateView(dateTabButton.getAttribute("data-date-tab"));
                return;
            }

            var dayButton = target.closest("[data-calendar-day]");

            if (dayButton && dateFilterPanel && dateFilterPanel.contains(dayButton)) {
                var selectedDay = dayButton.getAttribute("data-calendar-day");
                var selectedMonth = monthDefinitions[calendarViewMonthIndex].value;

                setActiveFilter("month", selectedMonth, { skipApply: true });
                setActiveFilter("year", String(calendarViewYear), { skipApply: true });
                setActiveFilter("day", selectedDay);
                refreshDatePickerUI();
                return;
            }

            var option = target.closest(".filter-option");

            if (!option || !filterNav.contains(option)) {
                return;
            }

            var group = option.getAttribute("data-filter-group");
            var value = option.getAttribute("data-filter-value");

            if (!group || !value) {
                return;
            }

            setActiveFilter(group, value);
            refreshDatePickerUI();
        });

        document.addEventListener("click", function (event) {
            var clickPath = typeof event.composedPath === "function" ? event.composedPath() : [];
            var clickedInsideFilterNav = clickPath.includes(filterNav) || filterNav.contains(event.target);

            if (!clickedInsideFilterNav) {
                closeFilterPanels(null);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                if (catalogFilterForm && !catalogFilterForm.hidden && catalogFilterForm.contains(document.activeElement)) {
                    document.querySelector('[aria-controls="catalog-filter-panel"]').focus();
                }
                closeFilterPanels(null);
            }
        });

        applyProductFilters();
    }

    adminNavBars.forEach(function (nav) {
        var swapButton = nav.querySelector("[data-admin-nav-swap]");
        var adminNavPills = nav.querySelectorAll("[data-admin-nav-pill]");
        var adminDashboardBaseUrl = String(nav.getAttribute("data-admin-dashboard-base-url") || "");
        var adminUsersFilter = nav.hasAttribute("data-admin-dashboard-nav") ? document.querySelector("[data-admin-users-filter]") : null;
        var adminUserRows = nav.hasAttribute("data-admin-dashboard-nav") ? document.querySelectorAll("[data-admin-user-row]") : [];
        var adminDefaultView = nav.hasAttribute("data-admin-dashboard-nav")
            ? String(document.body.getAttribute("data-admin-default-view") || "dashboard").toLowerCase()
            : "dashboard";
        var initialPanelTarget = nav.hasAttribute("data-admin-dashboard-nav")
            ? String(document.body.getAttribute("data-admin-initial-panel") || "").toLowerCase()
            : "";
        var dashboardDefaultSections = nav.hasAttribute("data-admin-dashboard-nav")
            ? document.querySelectorAll("[data-admin-dashboard-default]")
            : [];
        var dashboardPanels = nav.hasAttribute("data-admin-dashboard-nav")
            ? document.querySelectorAll("[data-admin-dashboard-panel]")
            : [];

        function setActiveAdminPill(nextPill) {
            adminNavPills.forEach(function (pill) {
                var isActive = pill === nextPill;

                pill.classList.toggle("is-active", isActive);

                if (isActive) {
                    pill.setAttribute("aria-current", "page");
                } else {
                    pill.removeAttribute("aria-current");
                }
            });

            if (nav.hasAttribute("data-admin-dashboard-nav")) {
                updateDashboardPanels();
                
                var panelTarget = nextPill ? nextPill.getAttribute("data-admin-panel-target") : null;
                if (panelTarget) {
                    var url = new URL(window.location.href);
                    url.searchParams.set("admin_view", panelTarget);
                    window.history.replaceState({ path: url.href }, "", url.href);
                }
            } else {
                var nextPanelTarget = nextPill ? String(nextPill.getAttribute("data-admin-panel-target") || "").toLowerCase() : "";

                if (nextPanelTarget && adminDashboardBaseUrl) {
                    var normalizedBase = adminDashboardBaseUrl;
                    var hasQuery = normalizedBase.indexOf("?") >= 0;
                    var hasHash = normalizedBase.indexOf("#") >= 0;

                    if (hasHash) {
                        normalizedBase = normalizedBase.split("#")[0];
                        hasQuery = normalizedBase.indexOf("?") >= 0;
                    }

                    var delimiter = hasQuery ? "&" : "?";
                    window.location.href = normalizedBase + delimiter + "admin_view=" + encodeURIComponent(nextPanelTarget);
                }
            }
        }

        function updateDashboardPanels() {
            if (!dashboardPanels.length && !dashboardDefaultSections.length) {
                return;
            }

            if (!nav.classList.contains("is-swapped")) {
                dashboardDefaultSections.forEach(function (section) {
                    var sectionDefaultView = String(section.getAttribute("data-admin-dashboard-default") || "dashboard").toLowerCase();
                    section.hidden = sectionDefaultView !== adminDefaultView;
                });

                dashboardPanels.forEach(function (panel) {
                    panel.hidden = true;
                });

                return;
            }

            var activePill = nav.querySelector("[data-admin-nav-pill].is-active");
            var activeTarget = activePill ? (activePill.getAttribute("data-admin-panel-target") || "") : "";

            dashboardDefaultSections.forEach(function (section) {
                section.hidden = true;
            });

            dashboardPanels.forEach(function (panel) {
                var panelName = panel.getAttribute("data-admin-dashboard-panel") || "";
                panel.hidden = panelName !== activeTarget;
            });
        }

        function applyAdminUsersFilter() {
            if (!adminUsersFilter || !adminUserRows.length) {
                return;
            }

            var selectedRole = String(adminUsersFilter.value || "all").toLowerCase();

            adminUserRows.forEach(function (row) {
                var rowRole = String(row.getAttribute("data-role") || "").toLowerCase();
                var shouldShow = selectedRole === "all" || rowRole === selectedRole;
                row.hidden = !shouldShow;
            });
        }

        if (!swapButton) {
            return;
        }

        if (initialPanelTarget) {
            var initialPill = nav.querySelector('[data-admin-nav-pill][data-admin-panel-target="' + initialPanelTarget + '"]');
            setAdminNavSwapState(nav, true);
            setActiveAdminPill(initialPill || adminNavPills[0] || null);
        } else {
            setAdminNavSwapState(nav, false);
            updateDashboardPanels();
        }

        applyAdminUsersFilter();

        swapButton.addEventListener("click", function () {
            var shouldSwap = !nav.classList.contains("is-swapped");

            if (shouldSwap && nav.classList.contains("section-nav-interactive")) {
                closeFilterPanels(null);
            }

            setAdminNavSwapState(nav, shouldSwap);

            if (shouldSwap) {
                if (nav.hasAttribute("data-admin-dashboard-nav")) {
                    setActiveAdminPill(adminNavPills[0] || null);
                }
            } else {
                updateDashboardPanels();
                if (nav.hasAttribute("data-admin-dashboard-nav")) {
                    var url = new URL(window.location.href);
                    url.searchParams.delete("admin_view");
                    window.history.replaceState({ path: url.href }, "", url.href);
                }
            }
        });

        adminNavPills.forEach(function (pill) {
            pill.addEventListener("click", function () {
                setActiveAdminPill(pill);
            });
        });

        if (adminUsersFilter) {
            adminUsersFilter.addEventListener("change", function () {
                applyAdminUsersFilter();
            });
        }
    });

    function syncAdminModalBodyLock() {
        var hasVisibleUsersModal = Boolean(adminUsersCreateBackdrop && !adminUsersCreateBackdrop.hidden);
        var hasVisibleEquipmentArchiveModal = Boolean(adminEquipmentArchiveBackdrop && !adminEquipmentArchiveBackdrop.hidden);
        var hasVisibleEquipmentStatusModal = Boolean(adminEquipmentStatusBackdrop && !adminEquipmentStatusBackdrop.hidden);
        var hasVisibleActionModal = Boolean(adminActionModalBackdrop && !adminActionModalBackdrop.hidden);
        var hasVisibleServiceEditModal = Boolean(adminServiceEditBackdrop && !adminServiceEditBackdrop.hidden);
        var hasVisibleEventEditModal = Boolean(adminEventEditBackdrop && !adminEventEditBackdrop.hidden);
        var hasVisibleEventThumbsModal = Boolean(adminEventThumbsBackdrop && !adminEventThumbsBackdrop.hidden);
        var hasVisibleServiceThumbsModal = Boolean(adminServiceThumbsBackdrop && !adminServiceThumbsBackdrop.hidden);
        var hasVisibleEventCollectionCreateModal = Boolean(adminEventCollectionCreateBackdrop && !adminEventCollectionCreateBackdrop.hidden);
        var hasVisibleEventCollectionEditModal = Boolean(adminEventCollectionEditBackdrop && !adminEventCollectionEditBackdrop.hidden);
        var hasVisibleBookingDetailModal = Boolean(adminBookingDetailBackdrop && !adminBookingDetailBackdrop.hidden);
        var hasVisibleBookingCancelModal = Boolean(adminBookingCancelBackdrop && !adminBookingCancelBackdrop.hidden);
        var hasVisibleBookingReviewModal = Boolean(adminBookingReviewBackdrop && !adminBookingReviewBackdrop.hidden);
        var hasVisibleBookingDeliveryModal = Boolean(adminBookingDeliveryBackdrop && !adminBookingDeliveryBackdrop.hidden);
        document.body.classList.toggle("admin-modal-open", hasVisibleUsersModal || hasVisibleEquipmentArchiveModal || hasVisibleEquipmentStatusModal || hasVisibleActionModal || hasVisibleServiceEditModal || hasVisibleEventEditModal || hasVisibleEventThumbsModal || hasVisibleServiceThumbsModal || hasVisibleEventCollectionCreateModal || hasVisibleEventCollectionEditModal || hasVisibleBookingDetailModal || hasVisibleBookingCancelModal || hasVisibleBookingReviewModal || hasVisibleBookingDeliveryModal);
    }

    function bookingReasonControl(backdrop, kind) {
        if (!backdrop) { return null; }
        return {
            choice: backdrop.querySelector('[data-admin-booking-' + kind + '-choice]'),
            custom: backdrop.querySelector('[data-admin-booking-' + kind + '-custom]'),
            input: backdrop.querySelector('[data-admin-booking-' + kind + '-reason]')
        };
    }

    function syncBookingReasonControl(control, focus) {
        if (!control || !control.choice || !control.input) { return; }
        var isOther = control.choice.value === 'others';
        control.custom.hidden = !isOther;
        control.input.disabled = !isOther;
        control.input.required = isOther;
        control.choice.setAttribute('aria-invalid', 'false');
        control.input.setAttribute('aria-invalid', 'false');
        if (focus && isOther) { control.input.focus(); }
    }

    function resetBookingReasonControl(control, status) {
        if (!control || !control.choice) { return; }
        control.choice.value = '';
        control.input.value = '';
        Array.prototype.forEach.call(control.choice.options, function (option) {
            var forStatus = option.getAttribute('data-reason-status');
            option.hidden = Boolean(forStatus && forStatus !== status);
            option.disabled = option.hidden;
        });
        syncBookingReasonControl(control, false);
    }

    function readBookingReasonControl(control) {
        if (!control || !control.choice) { return ''; }
        var option = control.choice.options[control.choice.selectedIndex];
        if (!option || option.disabled || !option.value) { return ''; }
        return option.value === 'others' ? String(control.input.value || '').trim() : option.textContent.trim();
    }

    function focusBookingReasonError(control) {
        if (!control || !control.choice) { return; }
        var field = control.choice.value === 'others' ? control.input : control.choice;
        field.setAttribute('aria-invalid', 'true');
        field.focus();
    }

    function setBookingReasonChoice(control) {
        var field = adminBookingStatusForm.querySelector('[data-admin-booking-reason-choice-input]');
        if (field) { field.value = control && control.choice ? control.choice.value : ''; }
    }

    var cancelReasonControl = bookingReasonControl(adminBookingCancelBackdrop, 'cancel');
    var reviewReasonControl = bookingReasonControl(adminBookingReviewBackdrop, 'review');
    var bookingReasonReturnFocus = null;
    [cancelReasonControl, reviewReasonControl].forEach(function (control) {
        if (!control || !control.choice) { return; }
        control.choice.addEventListener('change', function () {
            syncBookingReasonControl(control, true);
            if (control === cancelReasonControl) { setAdminBookingCancelError(''); }
            else { setAdminBookingReviewError(''); }
        });
    });

    function setAdminBookingCancelError(message) {
        if (!adminBookingCancelError) {
            return;
        }

        var text = String(message || "").trim();
        adminBookingCancelError.textContent = text;
        adminBookingCancelError.hidden = text === "";
    }

    function closeAdminBookingCancelModal() {
        if (!adminBookingCancelBackdrop) {
            return;
        }

        var wasVisible = !adminBookingCancelBackdrop.hidden;
        adminBookingCancelBackdrop.hidden = true;
        setAdminBookingCancelError("");

        if (adminBookingCancelReasonInput) {
            adminBookingCancelReasonInput.value = "";
        }

        if (adminBookingCancelConfirmButton) {
            adminBookingCancelConfirmButton.disabled = false;
        }

        syncAdminModalBodyLock();
        if (wasVisible && bookingReasonReturnFocus && bookingReasonReturnFocus.isConnected) {
            bookingReasonReturnFocus.focus();
        }
    }

    function openAdminBookingCancelModal() {
        if (!adminBookingCancelBackdrop || !adminBookingStatusOrderIdInput) {
            return;
        }

        var orderId = String(adminBookingStatusOrderIdInput.value || "").trim();
        if (!orderId) {
            return;
        }

        resetBookingReasonControl(cancelReasonControl, 'canceled');
        bookingReasonReturnFocus = document.activeElement;

        setAdminBookingCancelError("");
        adminBookingCancelBackdrop.hidden = false;
        syncAdminModalBodyLock();

        window.requestAnimationFrame(function () {
            if (cancelReasonControl && cancelReasonControl.choice) { cancelReasonControl.choice.focus(); }
        });
    }

    function setAdminBookingReviewError(message) {
        if (!adminBookingReviewError) {
            return;
        }

        var text = String(message || "").trim();
        adminBookingReviewError.textContent = text;
        adminBookingReviewError.hidden = text === "";
    }

    function resetAdminBookingReviewProofSelection() {
        if (adminBookingReviewProofFileInput) {
            adminBookingReviewProofFileInput.value = "";
        }

        if (adminBookingReviewProofFilename) {
            adminBookingReviewProofFilename.textContent = "No file selected";
        }
    }

    function closeAdminBookingReviewModal() {
        if (!adminBookingReviewBackdrop) {
            return;
        }

        var wasVisible = !adminBookingReviewBackdrop.hidden;
        adminBookingReviewBackdrop.hidden = true;
        activeAdminBookingReviewMode = "";
        setAdminBookingReviewError("");

        if (adminBookingReviewReasonInput) {
            adminBookingReviewReasonInput.value = "";
        }

        if (adminBookingReviewConfirmButton) {
            adminBookingReviewConfirmButton.disabled = false;
            adminBookingReviewConfirmButton.textContent = "Confirm";
        }

        if (adminBookingRefundProofHiddenInput) {
            adminBookingRefundProofHiddenInput.value = "";
        }

        if (adminBookingNextStatusInput) {
            adminBookingNextStatusInput.value = "";
        }

        if (adminBookingCancelReasonHiddenInput) {
            adminBookingCancelReasonHiddenInput.value = "";
        }

        if (adminBookingReviewProofWrap) {
            adminBookingReviewProofWrap.hidden = true;
        }

        if (adminBookingReviewCustomerGcashWrap) {
            adminBookingReviewCustomerGcashWrap.hidden = true;
        }

        if (adminBookingReviewCustomerGcashName) {
            adminBookingReviewCustomerGcashName.textContent = "-";
        }

        if (adminBookingReviewCustomerGcashNumber) {
            adminBookingReviewCustomerGcashNumber.textContent = "-";
        }

        resetAdminBookingReviewProofSelection();
        syncAdminModalBodyLock();
        if (wasVisible && bookingReasonReturnFocus && bookingReasonReturnFocus.isConnected) {
            bookingReasonReturnFocus.focus();
        }
    }

    function openAdminBookingReviewModal(mode) {
        if (!adminBookingReviewBackdrop || !adminBookingStatusOrderIdInput) {
            return;
        }

        var orderId = String(adminBookingStatusOrderIdInput.value || "").trim();
        if (!orderId) {
            return;
        }

        var bookingRecord = findAdminBookingById(orderId);
        var bookingGcashName = bookingRecord ? String(bookingRecord.customerGcashName || "").trim() : "";
        var bookingGcashNumber = bookingRecord ? String(bookingRecord.customerGcashNumber || "").trim() : "";

        var normalizedMode = mode === "refunded" ? "refunded" : "rejected";
        activeAdminBookingReviewMode = normalizedMode;
        resetBookingReasonControl(reviewReasonControl, normalizedMode);
        bookingReasonReturnFocus = document.activeElement;

        if (adminBookingReviewTitle) {
            adminBookingReviewTitle.textContent = normalizedMode === "refunded"
                ? "Mark Booking as Refunded"
                : "Reject Payment Receipt";
        }

        if (adminBookingReviewCopy) {
            adminBookingReviewCopy.textContent = normalizedMode === "refunded"
                ? "Provide a refund reason and upload the refund receipt screenshot. This will be visible to the customer."
                : "Provide a rejection reason. This will be visible to the customer in Order Status.";
        }

        if (adminBookingReviewConfirmButton) {
            adminBookingReviewConfirmButton.textContent = normalizedMode === "refunded"
                ? "Confirm Refund"
                : "Confirm Reject";
        }

        if (adminBookingReviewProofWrap) {
            adminBookingReviewProofWrap.hidden = normalizedMode !== "refunded";
        }

        if (adminBookingReviewCustomerGcashWrap) {
            adminBookingReviewCustomerGcashWrap.hidden = normalizedMode !== "refunded";
        }

        if (adminBookingReviewCustomerGcashName) {
            adminBookingReviewCustomerGcashName.textContent = bookingGcashName || "-";
        }

        if (adminBookingReviewCustomerGcashNumber) {
            adminBookingReviewCustomerGcashNumber.textContent = bookingGcashNumber || "-";
        }

        if (adminBookingReviewReasonInput) {
            adminBookingReviewReasonInput.value = "";
        }

        if (adminBookingRefundProofHiddenInput) {
            adminBookingRefundProofHiddenInput.value = "";
        }

        resetAdminBookingReviewProofSelection();
        setAdminBookingReviewError("");
        adminBookingReviewBackdrop.hidden = false;
        syncAdminModalBodyLock();

        window.requestAnimationFrame(function () {
            if (reviewReasonControl && reviewReasonControl.choice) { reviewReasonControl.choice.focus(); }
        });
    }

    function normalizeAdminDeliveryStatusToken(value, leg) {
        var normalizedLeg = String(leg || "").toLowerCase().trim() === "return"
            ? "return"
            : "receive";
        var token = String(value || "").toLowerCase().trim().replace(/[^a-z-]+/g, "");

        if (!token) {
            return normalizedLeg === "return" ? "waiting-customer-proof" : "waiting-proof";
        }

        if (token === "not-required") {
            return "not-required";
        }

        return token;
    }

    function formatAdminDeliveryStatusLabel(statusToken, leg) {
        var normalizedToken = normalizeAdminDeliveryStatusToken(statusToken, leg);

        if (normalizedToken === "not-required") {
            return "Not Required";
        }

        if (normalizedToken === "waiting-proof") {
            return "Waiting for Admin Upload";
        }

        if (normalizedToken === "waiting-customer-proof") {
            return "Waiting for Customer Upload";
        }

        if (normalizedToken === "in-transit") {
            return "In Transit";
        }

        if (normalizedToken === "closed") {
            return "Closed";
        }

        return normalizedToken
            ? normalizedToken.replace(/-/g, " ").replace(/\b\w/g, function (character) {
                return character.toUpperCase();
            })
            : "Waiting";
    }

    function formatAdminDeliveryActorLabel(actorToken) {
        var normalizedToken = String(actorToken || "").toLowerCase().trim();

        if (normalizedToken === "admin") {
            return "Admin";
        }

        if (normalizedToken === "customer") {
            return "Customer";
        }

        return "-";
    }

    function buildAdminDeliveryMetaText(config) {
        var source = config && typeof config === "object" ? config : {};
        var segments = [];
        var uploadedAtLabel = formatAdminBookingReceiptTimestamp(source.uploadedAt);
        var closedAtLabel = formatAdminBookingReceiptTimestamp(source.closedAt);
        var uploadedByLabel = formatAdminDeliveryActorLabel(source.uploadedBy);
        var closedByLabel = formatAdminDeliveryActorLabel(source.closedBy);
        var referenceText = String(source.reference || "").trim();
        var notesText = String(source.notes || "").trim();

        if (uploadedAtLabel) {
            if (uploadedByLabel !== "-") {
                segments.push("Uploaded by " + uploadedByLabel + " at " + uploadedAtLabel + ".");
            } else {
                segments.push("Uploaded at: " + uploadedAtLabel + ".");
            }
        } else if (uploadedByLabel !== "-") {
            segments.push("Uploaded by " + uploadedByLabel + ".");
        }

        if (referenceText) {
            segments.push("Reference: " + referenceText + ".");
        }

        if (notesText) {
            segments.push("Notes: " + notesText);
        }

        if (closedAtLabel) {
            if (closedByLabel !== "-") {
                segments.push("Closed by " + closedByLabel + " at " + closedAtLabel + ".");
            } else {
                segments.push("Closed at: " + closedAtLabel + ".");
            }
        }

        return segments.join(" ").trim();
    }

    function setAdminBookingDeliveryError(message) {
        if (!adminBookingDeliveryError) {
            return;
        }

        var text = String(message || "").trim();
        adminBookingDeliveryError.textContent = text;
        adminBookingDeliveryError.hidden = text === "";
    }

    function resetAdminBookingDeliveryProofSelection() {
        if (adminBookingDeliveryProofFileInput) {
            adminBookingDeliveryProofFileInput.value = "";
        }

        if (adminBookingDeliveryProofFilename) {
            adminBookingDeliveryProofFilename.textContent = "No file selected";
        }
    }

    function closeAdminBookingDeliveryModal() {
        if (!adminBookingDeliveryBackdrop) {
            return;
        }

        adminBookingDeliveryBackdrop.hidden = true;
        activeAdminBookingDeliveryMode = "";
        activeAdminBookingDeliveryOrderId = "";
        isSubmittingAdminBookingDelivery = false;

        setAdminBookingDeliverySubmittingState(false, "Confirm");

        if (adminBookingDeliveryProofWrap) {
            adminBookingDeliveryProofWrap.hidden = true;
        }

        if (adminBookingDeliveryReferenceInput) {
            adminBookingDeliveryReferenceInput.value = "";
        }

        if (adminBookingDeliveryNotesInput) {
            adminBookingDeliveryNotesInput.value = "";
        }

        resetAdminBookingDeliveryProofSelection();
        setAdminBookingDeliveryError("");
        syncAdminModalBodyLock();
    }

    function openAdminBookingDeliveryModal(mode) {
        if (!adminBookingDeliveryBackdrop || !adminBookingStatusOrderIdInput) {
            return;
        }

        var normalizedMode = String(mode || "").toLowerCase().trim();

        if (normalizedMode !== "upload-receive" && normalizedMode !== "close-receive" && normalizedMode !== "close-return") {
            return;
        }

        if (normalizedMode === "upload-receive" && !adminUploadDeliveryReceiptEndpoint) {
            return;
        }

        if ((normalizedMode === "close-receive" || normalizedMode === "close-return") && !adminCloseDeliveryLegEndpoint) {
            return;
        }

        var orderId = String(adminBookingStatusOrderIdInput.value || "").trim();
        if (!orderId) {
            return;
        }

        var bookingRecord = findAdminBookingById(orderId);
        var referenceValue = "";
        var notesValue = "";
        var titleText = "Delivery Action";
        var copyText = "Provide delivery details before confirming this action.";
        var confirmText = "Confirm";
        var shouldRequireProof = normalizedMode === "upload-receive";

        if (bookingRecord && typeof bookingRecord === "object") {
            if (normalizedMode === "close-return") {
                referenceValue = String(bookingRecord.returnDeliveryReference || "").trim();
                notesValue = String(bookingRecord.returnDeliveryNotes || "").trim();
            } else {
                referenceValue = String(bookingRecord.receiveDeliveryReference || "").trim();
                notesValue = String(bookingRecord.receiveDeliveryNotes || "").trim();
            }
        }

        if (normalizedMode === "upload-receive") {
            titleText = "Upload Receive Delivery Receipt";
            copyText = "Upload the courier proof to mark the receive-delivery leg as in transit.";
            confirmText = "Upload Receipt";
        } else if (normalizedMode === "close-receive") {
            titleText = "Close Receive Delivery Leg";
            copyText = "Confirm this leg only after the customer receives the equipment.";
            confirmText = "Close Receive Leg";
        } else {
            titleText = "Close Return Delivery Leg";
            copyText = "Confirm this leg only after returned equipment is back in-store.";
            confirmText = "Close Return Leg";
        }

        activeAdminBookingDeliveryMode = normalizedMode;
        activeAdminBookingDeliveryOrderId = orderId;
        isSubmittingAdminBookingDelivery = false;

        if (adminBookingDeliveryTitle) {
            adminBookingDeliveryTitle.textContent = titleText;
        }

        if (adminBookingDeliveryCopy) {
            adminBookingDeliveryCopy.textContent = copyText;
        }

        if (adminBookingDeliveryConfirmButton) {
            adminBookingDeliveryConfirmButton.textContent = confirmText;
            adminBookingDeliveryConfirmButton.disabled = false;
        }

        if (adminBookingDeliveryProofWrap) {
            adminBookingDeliveryProofWrap.hidden = !shouldRequireProof;
        }

        if (adminBookingDeliveryProofSelectButton) {
            adminBookingDeliveryProofSelectButton.disabled = false;
        }

        if (adminBookingDeliveryReferenceInput) {
            adminBookingDeliveryReferenceInput.value = referenceValue;
        }

        if (adminBookingDeliveryNotesInput) {
            adminBookingDeliveryNotesInput.value = notesValue;
        }

        resetAdminBookingDeliveryProofSelection();
        setAdminBookingDeliveryError("");
        adminBookingDeliveryBackdrop.hidden = false;
        syncAdminModalBodyLock();

        window.requestAnimationFrame(function () {
            if (shouldRequireProof && adminBookingDeliveryProofSelectButton) {
                adminBookingDeliveryProofSelectButton.focus();
                return;
            }

            if (adminBookingDeliveryReferenceInput) {
                adminBookingDeliveryReferenceInput.focus();
                adminBookingDeliveryReferenceInput.select();
            }
        });
    }

    function submitAdminDeliveryReceiptUpload(orderId, imageDataUrl, deliveryReference, deliveryNotes) {
        if (!adminUploadDeliveryReceiptEndpoint) {
            return Promise.reject(new Error("Receive-delivery upload endpoint is unavailable."));
        }

        return window.fetch(adminUploadDeliveryReceiptEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json"
            },
            body: JSON.stringify({
                orderId: String(orderId || ""),
                imageDataUrl: String(imageDataUrl || ""),
                deliveryReference: String(deliveryReference || ""),
                deliveryNotes: String(deliveryNotes || "")
            })
        }).then(function (response) {
            return response.text().then(function (rawBody) {
                var payload = {};

                try {
                    payload = JSON.parse(rawBody);
                } catch (error) {
                    payload = {};
                }

                if (!response.ok || !payload || payload.ok !== true) {
                    var errorMessage = payload && payload.message
                        ? String(payload.message)
                        : "Unable to upload receive-delivery receipt right now.";
                    throw new Error(errorMessage);
                }

                return payload;
            });
        });
    }

    function submitAdminCloseDeliveryLeg(orderId, leg, deliveryReference, deliveryNotes) {
        if (!adminCloseDeliveryLegEndpoint) {
            return Promise.reject(new Error("Close-delivery endpoint is unavailable."));
        }

        return window.fetch(adminCloseDeliveryLegEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json"
            },
            body: JSON.stringify({
                orderId: String(orderId || ""),
                leg: String(leg || ""),
                deliveryReference: String(deliveryReference || ""),
                deliveryNotes: String(deliveryNotes || "")
            })
        }).then(function (response) {
            return response.text().then(function (rawBody) {
                var payload = {};

                try {
                    payload = JSON.parse(rawBody);
                } catch (error) {
                    payload = {};
                }

                if (!response.ok || !payload || payload.ok !== true) {
                    var errorMessage = payload && payload.message
                        ? String(payload.message)
                        : "Unable to close delivery leg right now.";
                    throw new Error(errorMessage);
                }

                return payload;
            });
        });
    }

    function setAdminBookingDeliverySubmittingState(isSubmitting, label) {
        var disabled = Boolean(isSubmitting);
        var buttonLabel = String(label || "Confirm");

        if (adminBookingDeliveryConfirmButton) {
            adminBookingDeliveryConfirmButton.disabled = disabled;
            adminBookingDeliveryConfirmButton.textContent = buttonLabel;
        }

        if (adminBookingDeliveryProofSelectButton) {
            adminBookingDeliveryProofSelectButton.disabled = disabled;
        }

        adminBookingDeliveryCloseButtons.forEach(function (button) {
            button.disabled = disabled;
        });
    }

    function closeAdminActionModal(invokeOnClose) {
        if (!adminActionModalBackdrop) {
            return;
        }

        adminActionModalBackdrop.hidden = true;
        syncAdminModalBodyLock();

        var onCloseCallback = adminActionModalState.onClose;
        adminActionModalState.onConfirm = null;
        adminActionModalState.onClose = null;
        adminActionModalState.quantityRequired = false;

        if (adminActionModalConfirm) {
            adminActionModalConfirm.disabled = false;
            adminActionModalConfirm.textContent = "Confirm";
        }

        if (invokeOnClose && typeof onCloseCallback === "function") {
            onCloseCallback();
        }
    }

    function openAdminActionModal(options) {
        if (!adminActionModalBackdrop || !adminActionModalTitle || !adminActionModalMessage || !adminActionModalConfirm) {
            return;
        }

        var config = options || {};
        var requiresQuantity = Boolean(config.quantityRequired);
        var confirmLabel = String(config.confirmLabel || "Confirm");

        adminActionModalState.onConfirm = typeof config.onConfirm === "function" ? config.onConfirm : null;
        adminActionModalState.onClose = typeof config.onClose === "function" ? config.onClose : null;
        adminActionModalState.quantityRequired = requiresQuantity;

        adminActionModalTitle.textContent = String(config.title || "Confirm Action");
        adminActionModalMessage.textContent = String(config.message || "Please confirm this action.");
        adminActionModalConfirm.textContent = confirmLabel;
        adminActionModalConfirm.disabled = false;

        if (adminActionModalQuantityWrap && adminActionModalQuantityInput) {
            adminActionModalQuantityWrap.hidden = !requiresQuantity;

            if (requiresQuantity) {
                var minValue = Number.parseInt(String(config.quantityMin || "1"), 10);
                var maxValue = Number.parseInt(String(config.quantityMax || "200"), 10);
                var initialValue = Number.parseInt(String(config.quantityInitial || "1"), 10);

                if (!Number.isFinite(minValue) || minValue < 1) {
                    minValue = 1;
                }

                if (!Number.isFinite(maxValue) || maxValue < minValue) {
                    maxValue = 200;
                }

                if (!Number.isFinite(initialValue)) {
                    initialValue = minValue;
                }

                initialValue = Math.max(minValue, Math.min(maxValue, initialValue));

                adminActionModalQuantityInput.min = String(minValue);
                adminActionModalQuantityInput.max = String(maxValue);
                adminActionModalQuantityInput.value = String(initialValue);
            }
        }

        adminActionModalBackdrop.hidden = false;
        syncAdminModalBodyLock();

        window.requestAnimationFrame(function () {
            if (requiresQuantity && adminActionModalQuantityInput) {
                adminActionModalQuantityInput.focus();
                adminActionModalQuantityInput.select();
                return;
            }

            adminActionModalConfirm.focus();
        });
    }

    function openAdminUsersCreateModal() {
        if (!adminUsersCreateBackdrop) {
            return;
        }

        adminUsersCreateBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminUsersCreateModal() {
        if (!adminUsersCreateBackdrop) {
            return;
        }

        adminUsersCreateBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    function openAdminEquipmentArchiveModal() {
        if (!adminEquipmentArchiveBackdrop) {
            return;
        }

        adminEquipmentArchiveBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminEquipmentArchiveModal() {
        if (!adminEquipmentArchiveBackdrop) {
            return;
        }

        adminEquipmentArchiveBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    function openAdminEquipmentStatusModal() {
        if (!adminEquipmentStatusBackdrop) {
            return;
        }

        adminEquipmentStatusBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminEquipmentStatusModal() {
        if (!adminEquipmentStatusBackdrop) {
            return;
        }

        adminEquipmentStatusBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    function normalizeAdminBookingStatusClass(value) {
        var token = String(value || "status-pending")
            .toLowerCase()
            .replace(/[^a-z0-9-]+/g, "");

        if (token.indexOf("status-") !== 0) {
            return "status-pending";
        }

        return token;
    }

    function formatAdminBookingDate(value) {
        var normalized = String(value || "").trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
            return normalized;
        }

        var parts = normalized.split("-");
        var year = Number.parseInt(parts[0], 10);
        var month = Number.parseInt(parts[1], 10);
        var day = Number.parseInt(parts[2], 10);

        if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
            return normalized;
        }

        var parsed = new Date(year, month - 1, day);
        if (Number.isNaN(parsed.getTime())) {
            return normalized;
        }

        return parsed.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric"
        });
    }

    function formatAdminBookingTime(value) {
        var normalized = String(value || "").trim();
        var match = normalized.match(/^(\d{2}):(\d{2})$/);

        if (!match) {
            return normalized;
        }

        var hour = Number.parseInt(match[1], 10);
        var minute = Number.parseInt(match[2], 10);

        if (!Number.isFinite(hour) || !Number.isFinite(minute)) {
            return normalized;
        }

        var suffix = hour >= 12 ? "PM" : "AM";
        var hour12 = hour % 12;
        if (hour12 === 0) {
            hour12 = 12;
        }

        return String(hour12).padStart(2, "0") + ":" + String(minute).padStart(2, "0") + " " + suffix;
    }

    function formatAdminBookingSchedule(dateValue, timeValue) {
        var dateLabel = formatAdminBookingDate(dateValue);
        var timeLabel = formatAdminBookingTime(timeValue);

        if (dateLabel && timeLabel) {
            return dateLabel + " " + timeLabel;
        }

        return dateLabel || timeLabel || "-";
    }

    function parseAdminBookingScheduleToMs(dateValue, timeValue) {
        var dateToken = String(dateValue || "").trim();
        var timeToken = String(timeValue || "").trim();

        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateToken) || !/^\d{2}:\d{2}$/.test(timeToken)) {
            return Number.NaN;
        }

        var parsedValue = Date.parse(dateToken + "T" + timeToken + ":00");

        if (!Number.isFinite(parsedValue)) {
            return Number.NaN;
        }

        return parsedValue;
    }

    function formatAdminBookingDuration(receiveDateValue, receiveTimeValue, returnDateValue, returnTimeValue) {
        var startMs = parseAdminBookingScheduleToMs(receiveDateValue, receiveTimeValue);
        var endMs = parseAdminBookingScheduleToMs(returnDateValue, returnTimeValue);

        if (!Number.isFinite(startMs) || !Number.isFinite(endMs) || endMs <= startMs) {
            return "-";
        }

        var durationHours = Math.ceil((endMs - startMs) / (60 * 60 * 1000));

        if (durationHours < 24) {
            return durationHours + " hour" + (durationHours === 1 ? "" : "s");
        }

        var durationDays = durationHours / 24;
        var roundedDays = Math.abs(durationDays - Math.round(durationDays)) < 0.0001
            ? String(Math.round(durationDays))
            : String(durationDays.toFixed(1));

        return roundedDays + " day" + (roundedDays === "1" ? "" : "s");
    }

    function formatAdminBookingMethod(value, context) {
        var token = String(value || "").toLowerCase().trim();
        var normalizedContext = String(context || "").toLowerCase().trim();

        if (token === "pickup") {
            return normalizedContext === "returning" ? "Drop-off" : "Pick-up";
        }

        if (token === "meetup") {
            return "Meet-up";
        }

        if (token === "delivery") {
            return "Delivery";
        }

        return token ? token : "-";
    }

    function formatAdminBookingCourier(value) {
        var token = String(value || "").toLowerCase().trim();

        if (token === "grab-express") {
            return "GrabExpress";
        }

        if (token === "j-and-t") {
            return "J&T Express";
        }

        if (token === "self-booked") {
            return "Self-booked";
        }

        if (token === "lbc") {
            return "LBC";
        }

        if (token === "lalamove") {
            return "Lalamove";
        }

        return token ? token : "-";
    }

    function formatAdminBookingPayment(value) {
        var token = String(value || "").toLowerCase().trim();

        if (token === "gcash") {
            return "GCash";
        }

        if (token === "cash-pickup") {
            return "Cash on Pickup";
        }

        if (token === "cash-meetup") {
            return "Cash on Meetup";
        }

        return token ? token : "-";
    }

    function formatAdminBookingReceiptTimestamp(value) {
        var normalized = String(value || "").trim();

        if (!normalized) {
            return "";
        }

        var parsed = new Date(normalized);

        if (Number.isNaN(parsed.getTime())) {
            return normalized;
        }

        return parsed.toLocaleString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function parseAdminBookingTimestampToMs(value) {
        var normalized = String(value || "").trim();

        if (!normalized) {
            return Number.NaN;
        }

        var parsedValue = Date.parse(normalized);

        if (!Number.isFinite(parsedValue)) {
            return Number.NaN;
        }

        return parsedValue;
    }

    function formatAdminBookingCountdown(totalSeconds) {
        var parsedSeconds = Number.parseInt(totalSeconds, 10);
        var safeSeconds = Number.isFinite(parsedSeconds) && parsedSeconds > 0 ? parsedSeconds : 0;
        var hours = Math.floor(safeSeconds / 3600);
        var minutes = Math.floor((safeSeconds % 3600) / 60);
        var seconds = safeSeconds % 60;

        return padTwo(hours) + ":" + padTwo(minutes) + ":" + padTwo(seconds);
    }

    function getAdminBookingForReturnState(booking) {
        var defaultState = {
            active: false,
            remainingSeconds: 0,
            overdueSeconds: 0,
            penaltyPerHour: 50,
            penaltyHours: 0,
            penaltyAmount: 0
        };

        if (!booking || typeof booking !== "object") {
            return defaultState;
        }

        var statusToken = String(booking.statusToken || "").toLowerCase().trim();

        if (statusToken !== "return") {
            return defaultState;
        }

        var penaltyPerHourRaw = Number.parseInt(booking.forReturnPenaltyPerHour, 10);
        var penaltyPerHour = Number.isFinite(penaltyPerHourRaw) && penaltyPerHourRaw > 0
            ? penaltyPerHourRaw
            : 50;
        var deadlineMs = parseAdminBookingTimestampToMs(booking.forReturnDeadlineAt);

        if (!Number.isFinite(deadlineMs)) {
            return Object.assign({}, defaultState, {
                active: true,
                penaltyPerHour: penaltyPerHour
            });
        }

        var deltaMs = deadlineMs - Date.now();
        var remainingSeconds = Math.max(0, Math.ceil(deltaMs / 1000));
        var overdueSeconds = Math.max(0, Math.ceil((-deltaMs) / 1000));
        var penaltyHours = overdueSeconds > 0
            ? Math.ceil(overdueSeconds / 3600)
            : 0;

        return {
            active: true,
            remainingSeconds: remainingSeconds,
            overdueSeconds: overdueSeconds,
            penaltyPerHour: penaltyPerHour,
            penaltyHours: penaltyHours,
            penaltyAmount: penaltyHours * penaltyPerHour
        };
    }

    function findAdminBookingById(bookingId) {
        var targetId = String(bookingId || "").trim();

        if (!targetId) {
            return null;
        }

        var found = null;

        adminBookingsSource.some(function (entry) {
            if (!entry || typeof entry !== "object") {
                return false;
            }

            if (String(entry.id || "") !== targetId) {
                return false;
            }

            found = entry;
            return true;
        });

        return found;
    }

    function setAdminBookingDetailPage(pageName) {
        var normalizedPage = String(pageName || "").toLowerCase().trim();

        if (normalizedPage !== "images" && normalizedPage !== "details") {
            normalizedPage = "items";
        }

        activeAdminBookingDetailPage = normalizedPage;

        adminBookingDetailPageTabs.forEach(function (tabButton) {
            var tabPage = String(tabButton.getAttribute("data-admin-booking-page-tab") || "").toLowerCase().trim();
            var isActive = tabPage === normalizedPage;

            tabButton.classList.toggle("is-active", isActive);
            tabButton.setAttribute("aria-selected", isActive ? "true" : "false");
            tabButton.tabIndex = isActive ? 0 : -1;
        });

        adminBookingDetailPages.forEach(function (pageNode) {
            var pageToken = String(pageNode.getAttribute("data-admin-booking-page") || "").toLowerCase().trim();
            var isActive = pageToken === normalizedPage;

            pageNode.hidden = !isActive;
            pageNode.classList.toggle("is-active", isActive);
        });
    }

    function populateAdminBookingDetails(booking) {
        if (!booking) {
            return;
        }

        var statusClass = normalizeAdminBookingStatusClass(booking.statusClass || "status-pending");
        var statusTokenFromRecord = String(booking.statusToken || "").toLowerCase().trim();
        var statusToken = statusTokenFromRecord || statusClass.replace(/^status-/, "");
        var isApproved = statusToken === "approved";
        var isOngoing = statusToken === "ongoing";
        var isForReturn = statusToken === "return";
        var isCompleted = statusToken === "completed";
        var isCanceled = statusToken === "canceled";
        var isAwaitingRefund = statusToken === "awaiting-refund";
        var isRejected = statusToken === "rejected";
        var isRefunded = statusToken === "refunded";
        var isTerminalStatus = isCompleted || isCanceled || isRejected || isRefunded;
        var forReturnState = getAdminBookingForReturnState(booking);
        var paymentMethodToken = String(booking.paymentMethod || "").toLowerCase().trim();
        var customerGcashName = String(booking.customerGcashName || "").trim();
        var customerGcashNumber = String(booking.customerGcashNumber || "").trim();
        var paymentReceiptPath = String(booking.paymentReceiptPath || "").trim();
        var paymentReceiptUrl = String(booking.paymentReceiptUrl || "").trim();
        var paymentReceiptUploadedAt = String(booking.paymentReceiptUploadedAt || "").trim();
        var refundProofPath = String(booking.refundProofPath || "").trim();
        var refundProofUrl = String(booking.refundProofUrl || "").trim();
        var refundProofUploadedAt = String(booking.refundProofUploadedAt || "").trim();
        var validIdPath = String(booking.validIdPath || "").trim();
        var validIdUrl = String(booking.validIdUrl || "").trim();
        var validIdUploadedAt = String(booking.validIdUploadedAt || "").trim();
        var selfieWithIdPath = String(booking.selfieWithIdPath || "").trim();
        var selfieWithIdUrl = String(booking.selfieWithIdUrl || "").trim();
        var selfieWithIdUploadedAt = String(booking.selfieWithIdUploadedAt || "").trim();
        var requiresReceiveDelivery = Boolean(booking.requiresReceiveDelivery)
            || String(booking.receivingMethod || "").toLowerCase().trim() === "delivery";
        var requiresReturnDelivery = Boolean(booking.requiresReturnDelivery)
            || String(booking.returningMethod || "").toLowerCase().trim() === "delivery";
        var receiveDeliveryStatus = normalizeAdminDeliveryStatusToken(booking.receiveDeliveryStatus, "receive");
        var returnDeliveryStatus = normalizeAdminDeliveryStatusToken(booking.returnDeliveryStatus, "return");
        var receiveDeliveryReceiptPath = String(booking.receiveDeliveryReceiptPath || "").trim();
        var receiveDeliveryReceiptUrl = String(booking.receiveDeliveryReceiptUrl || "").trim();
        var receiveDeliveryReceiptUploadedAt = String(booking.receiveDeliveryReceiptUploadedAt || "").trim();
        var receiveDeliveryReceiptUploadedBy = String(booking.receiveDeliveryReceiptUploadedBy || "").trim();
        var receiveDeliveryReference = String(booking.receiveDeliveryReference || "").trim();
        var receiveDeliveryNotes = String(booking.receiveDeliveryNotes || "").trim();
        var receiveDeliveryClosedAt = String(booking.receiveDeliveryClosedAt || "").trim();
        var receiveDeliveryClosedBy = String(booking.receiveDeliveryClosedBy || "").trim();
        var returnDeliveryReceiptPath = String(booking.returnDeliveryReceiptPath || "").trim();
        var returnDeliveryReceiptUrl = String(booking.returnDeliveryReceiptUrl || "").trim();
        var returnDeliveryReceiptUploadedAt = String(booking.returnDeliveryReceiptUploadedAt || "").trim();
        var returnDeliveryReceiptUploadedBy = String(booking.returnDeliveryReceiptUploadedBy || "").trim();
        var returnDeliveryReference = String(booking.returnDeliveryReference || "").trim();
        var returnDeliveryNotes = String(booking.returnDeliveryNotes || "").trim();
        var returnDeliveryClosedAt = String(booking.returnDeliveryClosedAt || "").trim();
        var returnDeliveryClosedBy = String(booking.returnDeliveryClosedBy || "").trim();
        var requiresIdentityDocuments = Boolean(booking.requiresIdentityDocuments)
            || String(booking.receivingMethod || "").toLowerCase().trim() === "delivery"
            || String(booking.returningMethod || "").toLowerCase().trim() === "delivery";
        var hasPaymentReceipt = paymentReceiptUrl !== "" || paymentReceiptPath !== "";
        var isWaitingForPaymentReceipt = Boolean(booking.waitingForPaymentReceipt)
            || (statusToken === "pending" && paymentMethodToken === "gcash" && !hasPaymentReceipt);
        var isWaitingForPaymentReview = Boolean(booking.waitingForPaymentReview)
            || (statusToken === "pending" && paymentMethodToken === "gcash" && hasPaymentReceipt);
        var hasRefundProof = refundProofUrl !== "" || refundProofPath !== "";
        var receivingMethodToken = String(booking.receivingMethod || "").toLowerCase().trim();
        var receiveHandoverConfirmedAt = String(booking.receiveHandoverConfirmedAt || booking.receive_handover_confirmed_at || "").trim();
        var receiveHandoverConfirmed = Boolean(booking.receiveHandoverConfirmed)
            || receiveHandoverConfirmedAt !== "";
        var isForPickupReady = Boolean(booking.forPickupReady);

        if (!paymentReceiptUrl && paymentReceiptPath) {
            paymentReceiptUrl = paymentReceiptPath;
        }

        if (!refundProofUrl && refundProofPath) {
            refundProofUrl = refundProofPath;
        }

        if (!validIdUrl && validIdPath) {
            validIdUrl = validIdPath;
        }

        if (!selfieWithIdUrl && selfieWithIdPath) {
            selfieWithIdUrl = selfieWithIdPath;
        }

        if (!receiveDeliveryReceiptUrl && receiveDeliveryReceiptPath) {
            receiveDeliveryReceiptUrl = receiveDeliveryReceiptPath;
        }

        if (!returnDeliveryReceiptUrl && returnDeliveryReceiptPath) {
            returnDeliveryReceiptUrl = returnDeliveryReceiptPath;
        }

        var hasValidIdImage = validIdUrl !== "" || validIdPath !== "";
        var hasSelfieWithIdImage = selfieWithIdUrl !== "" || selfieWithIdPath !== "";
        var hasReceiveDeliveryReceipt = receiveDeliveryReceiptUrl !== "" || receiveDeliveryReceiptPath !== "";
        var hasReturnDeliveryReceipt = returnDeliveryReceiptUrl !== "" || returnDeliveryReceiptPath !== "";

        if (adminBookingDetailName) {
            adminBookingDetailName.textContent = String(booking.name || "-");
        }

        if (adminBookingDetailEmail) {
            var emailText = String(booking.email || "").trim();
            adminBookingDetailEmail.textContent = emailText || "-";
        }

        if (adminBookingDetailOrderNumber) {
            adminBookingDetailOrderNumber.textContent = String(booking.orderNumber || booking.id || "-");
        }

        if (adminBookingDetailTimestamp) {
            adminBookingDetailTimestamp.textContent = String(booking.timestamp || "-");
        }

        if (adminBookingDetailStatus) {
            adminBookingDetailStatus.className = "admin-bookings-status " + statusClass;
            adminBookingDetailStatus.textContent = String(booking.status || "PENDING");
        }

        if (adminBookingDetailPlace) {
            var placeText = String(booking.place || "").trim();
            adminBookingDetailPlace.textContent = placeText || "-";
        }

        if (adminBookingDetailReceive) {
            adminBookingDetailReceive.textContent = formatAdminBookingSchedule(booking.receiveDate, booking.receiveTime);
        }

        if (adminBookingDetailReturn) {
            adminBookingDetailReturn.textContent = formatAdminBookingSchedule(booking.returnDate, booking.returnTime);
        }

        if (adminBookingDetailDuration) {
            adminBookingDetailDuration.textContent = formatAdminBookingDuration(
                booking.receiveDate,
                booking.receiveTime,
                booking.returnDate,
                booking.returnTime
            );
        }

        if (adminBookingDetailReceivingMethod) {
            adminBookingDetailReceivingMethod.textContent = formatAdminBookingMethod(booking.receivingMethod, "receiving");
        }

        if (adminBookingDetailReturningMethod) {
            adminBookingDetailReturningMethod.textContent = formatAdminBookingMethod(booking.returningMethod, "returning");
        }

        if (adminBookingDetailCourier) {
            adminBookingDetailCourier.textContent = formatAdminBookingCourier(booking.courier);
        }

        if (adminBookingDetailPaymentMethod) {
            adminBookingDetailPaymentMethod.textContent = formatAdminBookingPayment(booking.paymentMethod);
        }

        if (adminBookingDetailCustomerGcashName) {
            adminBookingDetailCustomerGcashName.textContent = customerGcashName || "-";
        }

        if (adminBookingDetailCustomerGcashNumber) {
            adminBookingDetailCustomerGcashNumber.textContent = customerGcashNumber || "-";
        }

        if (adminBookingDetailReceiptState) {
            if (paymentMethodToken !== "gcash") {
                adminBookingDetailReceiptState.textContent = "Not Required";
            } else if (isWaitingForPaymentReceipt) {
                adminBookingDetailReceiptState.textContent = "Waiting for Upload";
            } else if (hasPaymentReceipt) {
                adminBookingDetailReceiptState.textContent = "Uploaded";
            } else {
                adminBookingDetailReceiptState.textContent = "Waiting for Upload";
            }
        }

        if (adminBookingDetailRefundProofState) {
            if (statusToken === "awaiting-refund") {
                adminBookingDetailRefundProofState.textContent = "Required";
            } else if (statusToken !== "refunded") {
                adminBookingDetailRefundProofState.textContent = "Not Required";
            } else if (hasRefundProof) {
                adminBookingDetailRefundProofState.textContent = "Uploaded";
            } else {
                adminBookingDetailRefundProofState.textContent = "Required";
            }
        }

        if (adminBookingDetailValidIdState) {
            if (!requiresIdentityDocuments) {
                adminBookingDetailValidIdState.textContent = "Not Required";
            } else if (hasValidIdImage) {
                adminBookingDetailValidIdState.textContent = "Uploaded";
            } else {
                adminBookingDetailValidIdState.textContent = "Missing";
            }
        }

        if (adminBookingDetailSelfieWithIdState) {
            if (!requiresIdentityDocuments) {
                adminBookingDetailSelfieWithIdState.textContent = "Not Required";
            } else if (hasSelfieWithIdImage) {
                adminBookingDetailSelfieWithIdState.textContent = "Uploaded";
            } else {
                adminBookingDetailSelfieWithIdState.textContent = "Missing";
            }
        }

        if (adminBookingDetailReceiptWrap) {
            adminBookingDetailReceiptWrap.hidden = paymentMethodToken !== "gcash";
        }

        if (adminBookingDetailReceiptLink) {
            adminBookingDetailReceiptLink.hidden = !hasPaymentReceipt;

            if (hasPaymentReceipt) {
                adminBookingDetailReceiptLink.href = paymentReceiptUrl;
            } else {
                adminBookingDetailReceiptLink.removeAttribute("href");
            }
        }

        if (adminBookingDetailReceiptImage) {
            if (hasPaymentReceipt) {
                adminBookingDetailReceiptImage.src = paymentReceiptUrl;
                adminBookingDetailReceiptImage.hidden = false;
            } else {
                adminBookingDetailReceiptImage.hidden = true;
                adminBookingDetailReceiptImage.removeAttribute("src");
            }
        }

        if (adminBookingDetailReceiptEmpty) {
            adminBookingDetailReceiptEmpty.hidden = hasPaymentReceipt || paymentMethodToken !== "gcash";
        }

        if (adminBookingDetailReceiptMeta) {
            if (hasPaymentReceipt) {
                var uploadedAtLabel = formatAdminBookingReceiptTimestamp(paymentReceiptUploadedAt);
                adminBookingDetailReceiptMeta.textContent = uploadedAtLabel
                    ? "Uploaded at: " + uploadedAtLabel
                    : "Payment receipt uploaded.";
                adminBookingDetailReceiptMeta.hidden = false;
            } else {
                adminBookingDetailReceiptMeta.hidden = true;
                adminBookingDetailReceiptMeta.textContent = "";
            }
        }

        if (adminBookingDetailRefundWrap) {
            adminBookingDetailRefundWrap.hidden = !isRefunded && !hasRefundProof;
        }

        if (adminBookingDetailRefundLink) {
            adminBookingDetailRefundLink.hidden = !hasRefundProof;

            if (hasRefundProof) {
                adminBookingDetailRefundLink.href = refundProofUrl;
            } else {
                adminBookingDetailRefundLink.removeAttribute("href");
            }
        }

        if (adminBookingDetailRefundImage) {
            if (hasRefundProof) {
                adminBookingDetailRefundImage.src = refundProofUrl;
                adminBookingDetailRefundImage.hidden = false;
            } else {
                adminBookingDetailRefundImage.hidden = true;
                adminBookingDetailRefundImage.removeAttribute("src");
            }
        }

        if (adminBookingDetailRefundEmpty) {
            adminBookingDetailRefundEmpty.hidden = hasRefundProof || !isRefunded;
        }

        if (adminBookingDetailRefundMeta) {
            if (hasRefundProof) {
                var refundUploadedAtLabel = formatAdminBookingReceiptTimestamp(refundProofUploadedAt);
                adminBookingDetailRefundMeta.textContent = refundUploadedAtLabel
                    ? "Uploaded at: " + refundUploadedAtLabel
                    : "Refund proof uploaded.";
                adminBookingDetailRefundMeta.hidden = false;
            } else {
                adminBookingDetailRefundMeta.hidden = true;
                adminBookingDetailRefundMeta.textContent = "";
            }
        }

        if (adminBookingDetailReceiveDeliveryState) {
            adminBookingDetailReceiveDeliveryState.textContent = formatAdminDeliveryStatusLabel(receiveDeliveryStatus, "receive");
        }

        if (adminBookingDetailReturnDeliveryState) {
            adminBookingDetailReturnDeliveryState.textContent = formatAdminDeliveryStatusLabel(returnDeliveryStatus, "return");
        }

        if (adminBookingDetailReceiveDeliveryWrap) {
            adminBookingDetailReceiveDeliveryWrap.hidden = !requiresReceiveDelivery && !hasReceiveDeliveryReceipt;
        }

        if (adminBookingDetailReturnDeliveryWrap) {
            adminBookingDetailReturnDeliveryWrap.hidden = !requiresReturnDelivery && !hasReturnDeliveryReceipt;
        }

        if (adminBookingDetailReceiveDeliveryLink) {
            adminBookingDetailReceiveDeliveryLink.hidden = !hasReceiveDeliveryReceipt;

            if (hasReceiveDeliveryReceipt) {
                adminBookingDetailReceiveDeliveryLink.href = receiveDeliveryReceiptUrl;
            } else {
                adminBookingDetailReceiveDeliveryLink.removeAttribute("href");
            }
        }

        if (adminBookingDetailReturnDeliveryLink) {
            adminBookingDetailReturnDeliveryLink.hidden = !hasReturnDeliveryReceipt;

            if (hasReturnDeliveryReceipt) {
                adminBookingDetailReturnDeliveryLink.href = returnDeliveryReceiptUrl;
            } else {
                adminBookingDetailReturnDeliveryLink.removeAttribute("href");
            }
        }

        if (adminBookingDetailReceiveDeliveryImage) {
            if (hasReceiveDeliveryReceipt) {
                adminBookingDetailReceiveDeliveryImage.src = receiveDeliveryReceiptUrl;
                adminBookingDetailReceiveDeliveryImage.hidden = false;
            } else {
                adminBookingDetailReceiveDeliveryImage.hidden = true;
                adminBookingDetailReceiveDeliveryImage.removeAttribute("src");
            }
        }

        if (adminBookingDetailReturnDeliveryImage) {
            if (hasReturnDeliveryReceipt) {
                adminBookingDetailReturnDeliveryImage.src = returnDeliveryReceiptUrl;
                adminBookingDetailReturnDeliveryImage.hidden = false;
            } else {
                adminBookingDetailReturnDeliveryImage.hidden = true;
                adminBookingDetailReturnDeliveryImage.removeAttribute("src");
            }
        }

        if (adminBookingDetailReceiveDeliveryEmpty) {
            adminBookingDetailReceiveDeliveryEmpty.hidden = hasReceiveDeliveryReceipt || !requiresReceiveDelivery;
        }

        if (adminBookingDetailReturnDeliveryEmpty) {
            adminBookingDetailReturnDeliveryEmpty.hidden = hasReturnDeliveryReceipt || !requiresReturnDelivery;
        }

        if (adminBookingDetailReceiveDeliveryMeta) {
            var receiveDeliveryMetaText = buildAdminDeliveryMetaText({
                uploadedAt: receiveDeliveryReceiptUploadedAt,
                uploadedBy: receiveDeliveryReceiptUploadedBy,
                reference: receiveDeliveryReference,
                notes: receiveDeliveryNotes,
                closedAt: receiveDeliveryClosedAt,
                closedBy: receiveDeliveryClosedBy
            });

            if (receiveDeliveryMetaText) {
                adminBookingDetailReceiveDeliveryMeta.textContent = receiveDeliveryMetaText;
                adminBookingDetailReceiveDeliveryMeta.hidden = false;
            } else {
                adminBookingDetailReceiveDeliveryMeta.textContent = "";
                adminBookingDetailReceiveDeliveryMeta.hidden = true;
            }
        }

        if (adminBookingDetailReturnDeliveryMeta) {
            var returnDeliveryMetaText = buildAdminDeliveryMetaText({
                uploadedAt: returnDeliveryReceiptUploadedAt,
                uploadedBy: returnDeliveryReceiptUploadedBy,
                reference: returnDeliveryReference,
                notes: returnDeliveryNotes,
                closedAt: returnDeliveryClosedAt,
                closedBy: returnDeliveryClosedBy
            });

            if (returnDeliveryMetaText) {
                adminBookingDetailReturnDeliveryMeta.textContent = returnDeliveryMetaText;
                adminBookingDetailReturnDeliveryMeta.hidden = false;
            } else {
                adminBookingDetailReturnDeliveryMeta.textContent = "";
                adminBookingDetailReturnDeliveryMeta.hidden = true;
            }
        }

        if (adminBookingDetailValidIdWrap) {
            adminBookingDetailValidIdWrap.hidden = !requiresIdentityDocuments && !hasValidIdImage;
        }

        if (adminBookingDetailValidIdLink) {
            adminBookingDetailValidIdLink.hidden = !hasValidIdImage;

            if (hasValidIdImage) {
                adminBookingDetailValidIdLink.href = validIdUrl;
            } else {
                adminBookingDetailValidIdLink.removeAttribute("href");
            }
        }

        if (adminBookingDetailValidIdImage) {
            if (hasValidIdImage) {
                adminBookingDetailValidIdImage.src = validIdUrl;
                adminBookingDetailValidIdImage.hidden = false;
            } else {
                adminBookingDetailValidIdImage.hidden = true;
                adminBookingDetailValidIdImage.removeAttribute("src");
            }
        }

        if (adminBookingDetailValidIdEmpty) {
            adminBookingDetailValidIdEmpty.hidden = hasValidIdImage || !requiresIdentityDocuments;
        }

        if (adminBookingDetailValidIdMeta) {
            if (hasValidIdImage) {
                var validIdUploadedAtLabel = formatAdminBookingReceiptTimestamp(validIdUploadedAt);
                adminBookingDetailValidIdMeta.textContent = validIdUploadedAtLabel
                    ? "Uploaded at: " + validIdUploadedAtLabel
                    : "Valid ID uploaded.";
                adminBookingDetailValidIdMeta.hidden = false;
            } else {
                adminBookingDetailValidIdMeta.hidden = true;
                adminBookingDetailValidIdMeta.textContent = "";
            }
        }

        if (adminBookingDetailSelfieWithIdWrap) {
            adminBookingDetailSelfieWithIdWrap.hidden = !requiresIdentityDocuments && !hasSelfieWithIdImage;
        }

        if (adminBookingDetailSelfieWithIdLink) {
            adminBookingDetailSelfieWithIdLink.hidden = !hasSelfieWithIdImage;

            if (hasSelfieWithIdImage) {
                adminBookingDetailSelfieWithIdLink.href = selfieWithIdUrl;
            } else {
                adminBookingDetailSelfieWithIdLink.removeAttribute("href");
            }
        }

        if (adminBookingDetailSelfieWithIdImage) {
            if (hasSelfieWithIdImage) {
                adminBookingDetailSelfieWithIdImage.src = selfieWithIdUrl;
                adminBookingDetailSelfieWithIdImage.hidden = false;
            } else {
                adminBookingDetailSelfieWithIdImage.hidden = true;
                adminBookingDetailSelfieWithIdImage.removeAttribute("src");
            }
        }

        if (adminBookingDetailSelfieWithIdEmpty) {
            adminBookingDetailSelfieWithIdEmpty.hidden = hasSelfieWithIdImage || !requiresIdentityDocuments;
        }

        if (adminBookingDetailSelfieWithIdMeta) {
            if (hasSelfieWithIdImage) {
                var selfieWithIdUploadedAtLabel = formatAdminBookingReceiptTimestamp(selfieWithIdUploadedAt);
                adminBookingDetailSelfieWithIdMeta.textContent = selfieWithIdUploadedAtLabel
                    ? "Uploaded at: " + selfieWithIdUploadedAtLabel
                    : "Selfie with ID uploaded.";
                adminBookingDetailSelfieWithIdMeta.hidden = false;
            } else {
                adminBookingDetailSelfieWithIdMeta.hidden = true;
                adminBookingDetailSelfieWithIdMeta.textContent = "";
            }
        }

        if (adminBookingDetailStatusNote) {
            if (isWaitingForPaymentReceipt) {
                adminBookingDetailStatusNote.textContent = requiresIdentityDocuments
                    ? "Waiting for payment receipt upload. Review Valid ID and selfie with ID, then cancel the reservation if verification is suspicious."
                    : "Waiting for payment receipt upload. Only cancellation is allowed while waiting.";
                adminBookingDetailStatusNote.hidden = false;
            } else if (isWaitingForPaymentReview) {
                adminBookingDetailStatusNote.textContent = requiresIdentityDocuments
                    ? "Payment receipt uploaded. Review Valid ID and selfie with ID, then choose Approve, Reject, Refund, or Cancel."
                    : "Payment receipt uploaded. Choose Approve, Reject, Refund, or Cancel to continue.";
                adminBookingDetailStatusNote.hidden = false;
            } else if (isAwaitingRefund) {
                adminBookingDetailStatusNote.textContent = "This approved reservation was canceled and is now awaiting refund. Upload refund proof to complete the process.";
                adminBookingDetailStatusNote.hidden = false;
            } else if (isApproved) {
                var approvedHandoverActionLabel = receivingMethodToken === "meetup"
                    ? "Confirm Meet-up Handover"
                    : "Confirm Pickup Handover";

                if (receivingMethodToken === "pickup" || receivingMethodToken === "meetup") {
                    if (!receiveHandoverConfirmed) {
                        adminBookingDetailStatusNote.textContent = (isForPickupReady
                            ? "Payment is approved and this reservation is now For Pickup. "
                            : "Payment is approved. ")
                            + "Use " + approvedHandoverActionLabel + " once the camera is physically handed over to the customer.";
                    } else {
                        adminBookingDetailStatusNote.textContent = "Payment is approved. Handover is already confirmed for this reservation.";
                    }
                } else {
                    if (receiveDeliveryStatus === "waiting-proof") {
                        adminBookingDetailStatusNote.textContent = "Payment is approved. Upload the receive-delivery receipt once courier handoff starts, then close the receive leg when the customer confirms delivery.";
                    } else if (receiveDeliveryStatus === "in-transit") {
                        adminBookingDetailStatusNote.textContent = "Receive delivery is in transit. Close the receive-delivery leg after successful customer handoff.";
                    } else if (receiveDeliveryStatus === "closed") {
                        adminBookingDetailStatusNote.textContent = "Receive delivery leg is closed. Status will switch to Ongoing automatically at the receiving schedule.";
                    } else {
                        adminBookingDetailStatusNote.textContent = "Payment is approved. Status will switch to Ongoing automatically at the receiving date/time.";
                    }
                }

                adminBookingDetailStatusNote.hidden = false;
            } else if (isOngoing) {
                var ongoingHandoverActionLabel = receivingMethodToken === "meetup"
                    ? "Confirm Meet-up Handover"
                    : "Confirm Pickup Handover";

                if ((receivingMethodToken === "pickup" || receivingMethodToken === "meetup") && !receiveHandoverConfirmed) {
                    adminBookingDetailStatusNote.textContent = "Camera is currently with the customer. If handover happened after the schedule time, use "
                        + ongoingHandoverActionLabel
                        + " to log the actual handover, then use Returned Early if needed.";
                } else {
                    adminBookingDetailStatusNote.textContent = "Camera is currently with the customer. You can only use Returned Early to complete this reservation before the scheduled return time.";
                }

                adminBookingDetailStatusNote.hidden = false;
            } else if (isForReturn) {
                var adminReturningModeLabel = formatAdminBookingMethod(booking.returningMethod, "returning");
                var adminForReturnReminder = "Please return the camera as soon as possible. Chosen return mode: "
                    + adminReturningModeLabel
                    + ".";

                if (requiresReturnDelivery) {
                    if (returnDeliveryStatus === "waiting-customer-proof") {
                        adminBookingDetailStatusNote.textContent = adminForReturnReminder + " Waiting for customer return-delivery receipt upload before courier transit can be tracked.";
                    } else if (returnDeliveryStatus === "in-transit") {
                        adminBookingDetailStatusNote.textContent = adminForReturnReminder + " Customer has uploaded the return-delivery receipt. Close the return-delivery leg once items are back in-store, then mark Complete.";
                    } else if (returnDeliveryStatus === "closed") {
                        adminBookingDetailStatusNote.textContent = adminForReturnReminder + " Return-delivery leg is closed. Mark Complete once final checks are done.";
                    } else {
                        adminBookingDetailStatusNote.textContent = adminForReturnReminder;
                    }
                } else if (forReturnState.active && forReturnState.overdueSeconds <= 0) {
                    adminBookingDetailStatusNote.textContent = adminForReturnReminder + " For Return grace time left: "
                        + formatAdminBookingCountdown(forReturnState.remainingSeconds)
                        + ". After this window, a penalty of \u20B1"
                        + Number(forReturnState.penaltyPerHour).toFixed(2)
                        + " per hour starts until manually completed.";
                } else if (forReturnState.active) {
                    adminBookingDetailStatusNote.textContent = adminForReturnReminder + " For Return overdue penalty: \u20B1"
                        + Number(forReturnState.penaltyAmount).toFixed(2)
                        + " (\u20B1"
                        + Number(forReturnState.penaltyPerHour).toFixed(2)
                        + "/hour). Settle this in person, then mark Complete manually.";
                } else {
                    adminBookingDetailStatusNote.textContent = adminForReturnReminder + " Booking is For Return. After the one-hour grace period, a \u20B1"
                        + Number(booking.forReturnPenaltyPerHour || 50).toFixed(2)
                        + "/hour penalty applies until manually completed.";
                }

                adminBookingDetailStatusNote.hidden = false;
            } else if (isCompleted) {
                adminBookingDetailStatusNote.textContent = "Booking is completed and cannot be changed.";
                adminBookingDetailStatusNote.hidden = false;
            } else if (isRejected) {
                adminBookingDetailStatusNote.textContent = "Payment receipt was rejected. This reservation cannot be changed.";
                adminBookingDetailStatusNote.hidden = false;
            } else if (isRefunded) {
                adminBookingDetailStatusNote.textContent = "Payment was refunded. This reservation cannot be changed.";
                adminBookingDetailStatusNote.hidden = false;
            } else if (isCanceled) {
                adminBookingDetailStatusNote.textContent = "This reservation is canceled and cannot be changed.";
                adminBookingDetailStatusNote.hidden = false;
            } else {
                adminBookingDetailStatusNote.textContent = "";
                adminBookingDetailStatusNote.hidden = true;
            }
        }

        var visibleSubmitStatusMap = {
            approved: false,
            "confirm-pickup-handover": false,
            "confirm-meetup-handover": false,
            "returned-early": false,
            completed: false
        };

        if (isWaitingForPaymentReview) {
            visibleSubmitStatusMap.approved = true;
        } else if (!isTerminalStatus && !isWaitingForPaymentReceipt && !isAwaitingRefund) {
            if (statusToken === "pending") {
                visibleSubmitStatusMap.approved = true;
            } else if (isOngoing) {
                visibleSubmitStatusMap["returned-early"] = true;
            } else if (isForReturn) {
                visibleSubmitStatusMap.completed = true;
            }
        } else if (isAwaitingRefund) {
            visibleSubmitStatusMap.approved = false;
            visibleSubmitStatusMap["confirm-pickup-handover"] = false;
            visibleSubmitStatusMap["confirm-meetup-handover"] = false;
            visibleSubmitStatusMap["returned-early"] = false;
            visibleSubmitStatusMap.completed = false;
        }

        if (!isTerminalStatus && !isWaitingForPaymentReceipt && !isWaitingForPaymentReview && !isAwaitingRefund && !receiveHandoverConfirmed) {
            var canConfirmReceiveHandover = statusToken === "approved" || isOngoing || isForReturn;

            if (canConfirmReceiveHandover && receivingMethodToken === "pickup") {
                visibleSubmitStatusMap["confirm-pickup-handover"] = true;
            }

            if (canConfirmReceiveHandover && receivingMethodToken === "meetup") {
                visibleSubmitStatusMap["confirm-meetup-handover"] = true;
            }
        }

        adminBookingStatusSubmitButtons.forEach(function (button) {
            var targetStatus = String(button.value || "").toLowerCase().trim();
            var isVisible = Boolean(visibleSubmitStatusMap[targetStatus]);
            button.disabled = !isVisible;
            button.hidden = !isVisible;
        });

        adminBookingReviewOpenButtons.forEach(function (button) {
            var mode = String(button.getAttribute("data-admin-booking-review-mode") || "").toLowerCase().trim();
            var isVisible = false;

            if (mode === "rejected") {
                isVisible = isWaitingForPaymentReview;
            } else if (mode === "refunded") {
                isVisible = isWaitingForPaymentReview || isAwaitingRefund;
            }

            button.disabled = !isVisible;
            button.hidden = !isVisible;
        });

        if (adminBookingCancelOpenButton) {
            var canCancelBooking = !isTerminalStatus
                && !isAwaitingRefund
                && (statusToken === "pending" || statusToken === "approved");
            adminBookingCancelOpenButton.disabled = !canCancelBooking;
            adminBookingCancelOpenButton.hidden = !canCancelBooking;
        }

        var canUploadReceiveDelivery = Boolean(adminUploadDeliveryReceiptEndpoint)
            && requiresReceiveDelivery
            && !isTerminalStatus
            && !isAwaitingRefund
            && (statusToken === "approved" || isOngoing)
            && (receiveDeliveryStatus === "waiting-proof" || receiveDeliveryStatus === "in-transit");
        var canCloseReceiveDelivery = Boolean(adminCloseDeliveryLegEndpoint)
            && requiresReceiveDelivery
            && !isTerminalStatus
            && !isAwaitingRefund
            && (statusToken === "approved" || isOngoing)
            && receiveDeliveryStatus === "in-transit"
            && hasReceiveDeliveryReceipt;
        var canCloseReturnDelivery = Boolean(adminCloseDeliveryLegEndpoint)
            && requiresReturnDelivery
            && !isTerminalStatus
            && !isAwaitingRefund
            && isForReturn
            && returnDeliveryStatus === "in-transit"
            && hasReturnDeliveryReceipt;

        adminBookingDeliveryActionButtons.forEach(function (button) {
            var mode = String(button.getAttribute("data-admin-booking-delivery-mode") || "").toLowerCase().trim();
            var isVisible = false;

            if (mode === "upload-receive") {
                isVisible = canUploadReceiveDelivery;
            } else if (mode === "close-receive") {
                isVisible = canCloseReceiveDelivery;
            } else if (mode === "close-return") {
                isVisible = canCloseReturnDelivery;
            }

            button.disabled = !isVisible;
            button.hidden = !isVisible;
        });

        if (adminBookingStatusForm) {
            var visibleActionCount = 0;

            adminBookingStatusForm.querySelectorAll(".admin-booking-action").forEach(function (button) {
                if (!button.hidden) {
                    visibleActionCount += 1;
                }
            });

            adminBookingStatusForm.classList.toggle("has-single-visible-action", visibleActionCount === 1);
        }

        if (adminBookingStatusForm) {
            adminBookingStatusForm.hidden = isTerminalStatus;

            if (isTerminalStatus) {
                adminBookingStatusForm.classList.remove("has-single-visible-action");
            }
        }

        activeAdminBookingCancelReason = String(booking.cancelReason || "").trim();

        if (adminBookingDetailCancelReason) {
            adminBookingDetailCancelReason.textContent = activeAdminBookingCancelReason || "-";
        }

        if (adminBookingStatusOrderIdInput) {
            adminBookingStatusOrderIdInput.value = String(booking.id || "");
        }

        if (adminBookingNextStatusInput) {
            adminBookingNextStatusInput.value = "";
        }

        if (adminBookingCancelReasonHiddenInput) {
            adminBookingCancelReasonHiddenInput.value = "";
        }

        if (adminBookingRefundProofHiddenInput) {
            adminBookingRefundProofHiddenInput.value = "";
        }

        if (adminBookingDetailItems) {
            adminBookingDetailItems.innerHTML = "";

            var bookingItems = Array.isArray(booking.items) ? booking.items : [];
            var totalQuantity = 0;
            var totalDayUnits = 0;
            var hasEstimatedAmount = false;
            var estimatedAmount = 0;

            if (!bookingItems.length) {
                var emptyItem = document.createElement("li");
                emptyItem.className = "admin-booking-detail-item is-empty";
                emptyItem.textContent = "No item details found.";
                adminBookingDetailItems.appendChild(emptyItem);
            } else {
                bookingItems.forEach(function (item) {
                    if (!item || typeof item !== "object") {
                        return;
                    }

                    var name = String(item.name || "Item");
                    var qty = Number.parseInt(item.qty, 10);
                    var days = Number.parseInt(item.days, 10);

                    if (!Number.isFinite(qty) || qty < 1) {
                        qty = 1;
                    }

                    if (!Number.isFinite(days) || days < 1) {
                        days = 1;
                    }

                    totalQuantity += qty;
                    totalDayUnits += qty * days;

                    var lineRate = Number.NaN;
                    [item.price, item.unitPrice, item.dailyRate, item.rate].some(function (candidate) {
                        var parsedCandidate = Number.parseFloat(candidate);

                        if (!Number.isFinite(parsedCandidate) || parsedCandidate < 0) {
                            return false;
                        }

                        lineRate = parsedCandidate;
                        return true;
                    });

                    var lineAmount = Number.isFinite(lineRate) ? lineRate * qty * days : Number.NaN;

                    if (Number.isFinite(lineAmount)) {
                        hasEstimatedAmount = true;
                        estimatedAmount += lineAmount;
                    }

                    var imageUrl = String(item.imageUrl || item.image_url || "").trim();

                    var entry = document.createElement("li");
                    entry.className = "admin-booking-detail-item";

                    var nameElement = document.createElement("p");
                    nameElement.className = "admin-booking-detail-item-name";
                    nameElement.textContent = name;

                    var bodyElement = document.createElement("div");
                    bodyElement.className = "admin-booking-detail-item-body";

                    var mediaElement = document.createElement("div");
                    mediaElement.className = "admin-booking-detail-item-media";

                    if (imageUrl) {
                        var imageElement = document.createElement("img");
                        imageElement.className = "admin-booking-detail-item-thumb";
                        imageElement.src = imageUrl;
                        imageElement.alt = name;
                        imageElement.loading = "lazy";
                        mediaElement.appendChild(imageElement);
                    } else {
                        var placeholderElement = document.createElement("div");
                        placeholderElement.className = "admin-booking-detail-item-thumb is-placeholder";
                        placeholderElement.textContent = "Preview unavailable";
                        mediaElement.appendChild(placeholderElement);
                    }

                    var statsElement = document.createElement("div");
                    statsElement.className = "admin-booking-detail-item-stats";

                    var assignedUnitIds = [];
                    var rawAssignedUnitIds = Array.isArray(item.assigned_unit_ids)
                        ? item.assigned_unit_ids
                        : (Array.isArray(item.assignedUnitIds) ? item.assignedUnitIds : []);
                    var fallbackAssignedUnitId = String(item.assigned_unit_id || item.assignedUnitId || "").trim();

                    if (!rawAssignedUnitIds.length && fallbackAssignedUnitId) {
                        rawAssignedUnitIds = [fallbackAssignedUnitId];
                    }

                    rawAssignedUnitIds.forEach(function (candidate) {
                        var normalizedCandidate = String(candidate || "").trim().toUpperCase();

                        if (!normalizedCandidate || assignedUnitIds.indexOf(normalizedCandidate) !== -1) {
                            return;
                        }

                        assignedUnitIds.push(normalizedCandidate);
                    });

                    var metaRowElement = document.createElement("div");
                    metaRowElement.className = "admin-booking-detail-item-meta-row";

                    var qtyElement = document.createElement("p");
                    qtyElement.className = "admin-booking-detail-item-meta";

                    var qtyLabelElement = document.createElement("span");
                    qtyLabelElement.className = "admin-booking-detail-item-meta-label";
                    qtyLabelElement.textContent = "Qty / Days:";

                    var qtyValueElement = document.createElement("span");
                    qtyValueElement.className = "admin-booking-detail-item-meta-value";
                    qtyValueElement.textContent = String(qty) + " / " + String(days);

                    qtyElement.appendChild(qtyLabelElement);
                    qtyElement.appendChild(document.createTextNode(" "));
                    qtyElement.appendChild(qtyValueElement);

                    var unitIdElement = document.createElement("p");
                    unitIdElement.className = "admin-booking-detail-item-meta is-unit-id";

                    var unitIdLabelElement = document.createElement("span");
                    unitIdLabelElement.className = "admin-booking-detail-item-meta-label";
                    unitIdLabelElement.textContent = assignedUnitIds.length > 1 ? "Unit IDs:" : "Unit ID:";

                    var unitIdValueElement = document.createElement("span");
                    unitIdValueElement.className = "admin-booking-detail-item-meta-value";
                    unitIdValueElement.textContent = assignedUnitIds.length
                        ? assignedUnitIds.join(", ")
                        : "-";

                    unitIdElement.appendChild(unitIdLabelElement);
                    unitIdElement.appendChild(document.createTextNode(" "));
                    unitIdElement.appendChild(unitIdValueElement);

                    metaRowElement.appendChild(qtyElement);
                    metaRowElement.appendChild(unitIdElement);
                    statsElement.appendChild(metaRowElement);

                    if (Number.isFinite(lineAmount)) {
                        var estimateElement = document.createElement("p");
                        estimateElement.className = "admin-booking-detail-item-meta is-estimate";
                        estimateElement.textContent = "Est.: " + formatMoney(lineAmount);
                        statsElement.appendChild(estimateElement);
                    }

                    bodyElement.appendChild(mediaElement);
                    bodyElement.appendChild(statsElement);
                    entry.appendChild(nameElement);
                    entry.appendChild(bodyElement);

                    adminBookingDetailItems.appendChild(entry);
                });
            }

            if (adminBookingDetailItemsTotal) {
                var quantityLabel = totalQuantity === 1 ? "item" : "items";
                var dayUnitLabel = totalDayUnits === 1 ? "day-unit" : "day-units";
                var totalLabel = "Total quantity: " + totalQuantity + " " + quantityLabel
                    + " | Rental load: " + totalDayUnits + " " + dayUnitLabel;

                if (hasEstimatedAmount) {
                    totalLabel += " | Estimated total: " + formatMoney(estimatedAmount);
                }

                adminBookingDetailItemsTotal.textContent = totalLabel;
            }
        }
    }

    function openAdminBookingDetail(bookingId) {
        if (!adminBookingDetailBackdrop) {
            return;
        }

        var booking = findAdminBookingById(bookingId);
        if (!booking) {
            return;
        }

        populateAdminBookingDetails(booking);
        setAdminBookingDetailPage("items");
        adminBookingDetailBackdrop.hidden = false;
        syncAdminModalBodyLock();
    }

    function closeAdminBookingDetail() {
        if (!adminBookingDetailBackdrop) {
            return;
        }

        closeAdminBookingDeliveryModal();
        closeAdminBookingReviewModal();
        closeAdminBookingCancelModal();

        adminBookingDetailBackdrop.hidden = true;
        syncAdminModalBodyLock();
    }

    adminUsersOpenModalButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openAdminUsersCreateModal();
        });
    });

    adminUsersCloseModalButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminUsersCreateModal();
        });
    });

    if (adminUsersCreateBackdrop) {
        adminUsersCreateBackdrop.addEventListener("click", function (event) {
            if (event.target === adminUsersCreateBackdrop) {
                closeAdminUsersCreateModal();
            }
        });
    }

    adminEquipmentArchiveOpenButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openAdminEquipmentArchiveModal();
        });
    });

    adminEquipmentArchiveCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminEquipmentArchiveModal();
        });
    });

    if (adminEquipmentArchiveBackdrop) {
        adminEquipmentArchiveBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEquipmentArchiveBackdrop) {
                closeAdminEquipmentArchiveModal();
            }
        });
    }

    adminEquipmentStatusOpenButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            openAdminEquipmentStatusModal();
        });
    });

    adminEquipmentStatusCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminEquipmentStatusModal();
        });
    });

    if (adminEquipmentStatusBackdrop) {
        adminEquipmentStatusBackdrop.addEventListener("click", function (event) {
            if (event.target === adminEquipmentStatusBackdrop) {
                closeAdminEquipmentStatusModal();
            }
        });
    }

    adminBookingRows.forEach(function (row) {
        row.addEventListener("click", function () {
            openAdminBookingDetail(row.getAttribute("data-admin-booking-id") || "");
        });

        row.addEventListener("keydown", function (event) {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            openAdminBookingDetail(row.getAttribute("data-admin-booking-id") || "");
        });
    });

    adminBookingDetailPageTabs.forEach(function (button) {
        button.addEventListener("click", function () {
            if (!button || button.disabled) {
                return;
            }

            setAdminBookingDetailPage(button.getAttribute("data-admin-booking-page-tab") || "items");
        });
    });

    adminBookingDetailCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminBookingDetail();
        });
    });

    if (adminBookingDetailBackdrop) {
        adminBookingDetailBackdrop.addEventListener("click", function (event) {
            if (event.target === adminBookingDetailBackdrop) {
                closeAdminBookingDetail();
            }
        });
    }

    adminBookingStatusSubmitButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            if (adminBookingNextStatusInput) {
                adminBookingNextStatusInput.value = "";
            }

            if (adminBookingCancelReasonHiddenInput) {
                adminBookingCancelReasonHiddenInput.value = "";
            }

            if (adminBookingRefundProofHiddenInput) {
                adminBookingRefundProofHiddenInput.value = "";
            }
        });
    });

    adminBookingReviewOpenButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            if (!button || button.disabled || button.hidden) {
                return;
            }

            var mode = String(button.getAttribute("data-admin-booking-review-mode") || "").toLowerCase().trim();
            openAdminBookingReviewModal(mode);
        });
    });

    if (adminBookingCancelOpenButton) {
        adminBookingCancelOpenButton.addEventListener("click", function () {
            openAdminBookingCancelModal();
        });
    }

    adminBookingDeliveryActionButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            if (!button || button.disabled || button.hidden) {
                return;
            }

            var mode = String(button.getAttribute("data-admin-booking-delivery-mode") || "").toLowerCase().trim();
            openAdminBookingDeliveryModal(mode);
        });
    });

    adminBookingDeliveryCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            if (isSubmittingAdminBookingDelivery) {
                return;
            }

            closeAdminBookingDeliveryModal();
        });
    });

    if (adminBookingDeliveryBackdrop) {
        adminBookingDeliveryBackdrop.addEventListener("click", function (event) {
            if (event.target !== adminBookingDeliveryBackdrop || isSubmittingAdminBookingDelivery) {
                return;
            }

            closeAdminBookingDeliveryModal();
        });
    }

    if (adminBookingDeliveryProofSelectButton) {
        adminBookingDeliveryProofSelectButton.addEventListener("click", function () {
            if (isSubmittingAdminBookingDelivery || !adminBookingDeliveryProofFileInput) {
                return;
            }

            if (activeAdminBookingDeliveryMode !== "upload-receive") {
                return;
            }

            adminBookingDeliveryProofFileInput.click();
        });
    }

    if (adminBookingDeliveryProofFileInput) {
        adminBookingDeliveryProofFileInput.addEventListener("change", function () {
            if (adminBookingDeliveryProofFilename) {
                if (adminBookingDeliveryProofFileInput.files && adminBookingDeliveryProofFileInput.files.length > 0) {
                    adminBookingDeliveryProofFilename.textContent = adminBookingDeliveryProofFileInput.files[0].name;
                } else {
                    adminBookingDeliveryProofFilename.textContent = "No file selected";
                }
            }

            setAdminBookingDeliveryError("");
        });
    }

    if (adminBookingDeliveryConfirmButton) {
        adminBookingDeliveryConfirmButton.addEventListener("click", function () {
            if (isSubmittingAdminBookingDelivery) {
                return;
            }

            var orderId = String(activeAdminBookingDeliveryOrderId || (adminBookingStatusOrderIdInput ? adminBookingStatusOrderIdInput.value : "") || "").trim();
            var mode = String(activeAdminBookingDeliveryMode || "").toLowerCase().trim();
            var deliveryReference = adminBookingDeliveryReferenceInput
                ? String(adminBookingDeliveryReferenceInput.value || "").trim()
                : "";
            var deliveryNotes = adminBookingDeliveryNotesInput
                ? String(adminBookingDeliveryNotesInput.value || "").trim()
                : "";

            if (!orderId || (mode !== "upload-receive" && mode !== "close-receive" && mode !== "close-return")) {
                closeAdminBookingDeliveryModal();
                return;
            }

            var resetSubmitState = function (label) {
                isSubmittingAdminBookingDelivery = false;
                setAdminBookingDeliverySubmittingState(false, label || "Confirm");
            };
            var confirmLabel = mode === "upload-receive"
                ? "Upload Receipt"
                : (mode === "close-return" ? "Close Return Leg" : "Close Receive Leg");

            setAdminBookingDeliveryError("");
            isSubmittingAdminBookingDelivery = true;
            setAdminBookingDeliverySubmittingState(true, "Submitting...");

            var onSuccess = function () {
                closeAdminBookingDeliveryModal();
                window.location.reload();
            };

            var onFailure = function (error) {
                setAdminBookingDeliveryError(error && error.message
                    ? String(error.message)
                    : "Unable to process delivery action right now.");
                resetSubmitState(confirmLabel);
            };

            if (mode !== "upload-receive") {
                var leg = mode === "close-return" ? "return" : "receive";

                submitAdminCloseDeliveryLeg(orderId, leg, deliveryReference, deliveryNotes)
                    .then(onSuccess)
                    .catch(onFailure);
                return;
            }

            if (!adminBookingDeliveryProofFileInput || !adminBookingDeliveryProofFileInput.files || !adminBookingDeliveryProofFileInput.files.length) {
                setAdminBookingDeliveryError("Please upload a receipt image before submitting.");
                resetSubmitState(confirmLabel);
                return;
            }

            var selectedFile = adminBookingDeliveryProofFileInput.files[0];
            var reader = new FileReader();

            reader.onload = function (loadEvent) {
                var imageDataUrl = String(loadEvent && loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");

                if (imageDataUrl.indexOf("data:image/") !== 0) {
                    setAdminBookingDeliveryError("Please upload a valid image file.");
                    resetSubmitState(confirmLabel);
                    return;
                }

                submitAdminDeliveryReceiptUpload(orderId, imageDataUrl, deliveryReference, deliveryNotes)
                    .then(onSuccess)
                    .catch(onFailure);
            };

            reader.onerror = function () {
                setAdminBookingDeliveryError("Unable to read the selected receipt image.");
                resetSubmitState(confirmLabel);
            };

            reader.readAsDataURL(selectedFile);
        });
    }

    adminBookingCancelCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminBookingCancelModal();
        });
    });

    if (adminBookingCancelBackdrop) {
        adminBookingCancelBackdrop.addEventListener("click", function (event) {
            if (event.target === adminBookingCancelBackdrop) {
                closeAdminBookingCancelModal();
            }
        });
    }

    if (adminBookingCancelConfirmButton) {
        adminBookingCancelConfirmButton.addEventListener("click", function () {
            if (!adminBookingStatusForm || !adminBookingStatusOrderIdInput) {
                return;
            }

            var orderId = String(adminBookingStatusOrderIdInput.value || "").trim();
            var reasonText = readBookingReasonControl(cancelReasonControl);

            if (!orderId) {
                closeAdminBookingCancelModal();
                return;
            }

            if (reasonText === "") {
                setAdminBookingCancelError("Select a reason, or choose Others and enter an explanation.");
                focusBookingReasonError(cancelReasonControl);

                return;
            }

            if (adminBookingCancelConfirmButton.disabled) {
                return;
            }

            if (adminBookingNextStatusInput) {
                adminBookingNextStatusInput.value = "canceled";
            }

            if (adminBookingCancelReasonHiddenInput) {
                adminBookingCancelReasonHiddenInput.value = reasonText;
            }

            adminBookingCancelConfirmButton.disabled = true;
            setBookingReasonChoice(cancelReasonControl);
            adminBookingStatusForm.submit();
        });
    }

    adminBookingReviewCloseButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminBookingReviewModal();
        });
    });

    if (adminBookingReviewBackdrop) {
        adminBookingReviewBackdrop.addEventListener("click", function (event) {
            if (event.target === adminBookingReviewBackdrop) {
                closeAdminBookingReviewModal();
            }
        });
    }

    if (adminBookingReviewProofSelectButton) {
        adminBookingReviewProofSelectButton.addEventListener("click", function () {
            if (!adminBookingReviewProofFileInput || !activeAdminBookingReviewMode || activeAdminBookingReviewMode !== "refunded") {
                return;
            }

            if (adminBookingReviewConfirmButton && adminBookingReviewConfirmButton.disabled) {
                return;
            }

            adminBookingReviewProofFileInput.click();
        });
    }

    if (adminBookingReviewProofFileInput) {
        adminBookingReviewProofFileInput.addEventListener("change", function () {
            if (adminBookingReviewProofFilename) {
                if (adminBookingReviewProofFileInput.files && adminBookingReviewProofFileInput.files.length > 0) {
                    adminBookingReviewProofFilename.textContent = adminBookingReviewProofFileInput.files[0].name;
                } else {
                    adminBookingReviewProofFilename.textContent = "No file selected";
                }
            }

            setAdminBookingReviewError("");
        });
    }

    if (adminBookingReviewConfirmButton) {
        adminBookingReviewConfirmButton.addEventListener("click", function () {
            if (!adminBookingStatusForm || !adminBookingStatusOrderIdInput || !adminBookingNextStatusInput || !adminBookingCancelReasonHiddenInput) {
                return;
            }

            if (adminBookingReviewConfirmButton.disabled) {
                return;
            }

            if (activeAdminBookingReviewMode !== "rejected" && activeAdminBookingReviewMode !== "refunded") {
                return;
            }

            var orderId = String(adminBookingStatusOrderIdInput.value || "").trim();
            var reviewMode = activeAdminBookingReviewMode === "refunded" ? "refunded" : "rejected";
            var reasonText = readBookingReasonControl(reviewReasonControl);
            var confirmLabel = reviewMode === "refunded" ? "Confirm Refund" : "Confirm Reject";

            if (!orderId) {
                closeAdminBookingReviewModal();
                return;
            }

            if (reasonText === "") {
                setAdminBookingReviewError("Select a reason, or choose Others and enter an explanation.");
                focusBookingReasonError(reviewReasonControl);

                return;
            }

            adminBookingNextStatusInput.value = reviewMode;
            adminBookingCancelReasonHiddenInput.value = reasonText;

            if (adminBookingRefundProofHiddenInput) {
                adminBookingRefundProofHiddenInput.value = "";
            }

            var submitDecision = function () {
                if (adminBookingReviewBackdrop.hidden || activeAdminBookingReviewMode !== reviewMode) { return; }
                adminBookingReviewConfirmButton.disabled = true;
                setBookingReasonChoice(reviewReasonControl);
                adminBookingStatusForm.submit();
            };

            if (reviewMode !== "refunded") {
                submitDecision();
                return;
            }

            if (!adminBookingReviewProofFileInput || !adminBookingReviewProofFileInput.files || !adminBookingReviewProofFileInput.files.length) {
                setAdminBookingReviewError("Please upload a refund proof image.");
                return;
            }

            var selectedFile = adminBookingReviewProofFileInput.files[0];
            var reader = new FileReader();

            adminBookingReviewConfirmButton.disabled = true;
            adminBookingReviewConfirmButton.textContent = "Submitting...";
            setAdminBookingReviewError("");

            reader.onload = function (loadEvent) {
                if (adminBookingReviewBackdrop.hidden || activeAdminBookingReviewMode !== reviewMode) { return; }
                var imageDataUrl = String(loadEvent && loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");

                if (imageDataUrl.indexOf("data:image/") !== 0) {
                    setAdminBookingReviewError("Please upload a valid image file.");
                    adminBookingReviewConfirmButton.disabled = false;
                    adminBookingReviewConfirmButton.textContent = confirmLabel;
                    return;
                }

                if (adminBookingRefundProofHiddenInput) {
                    adminBookingRefundProofHiddenInput.value = imageDataUrl;
                }

                submitDecision();
            };

            reader.onerror = function () {
                setAdminBookingReviewError("Unable to read the selected proof image.");
                adminBookingReviewConfirmButton.disabled = false;
                adminBookingReviewConfirmButton.textContent = confirmLabel;
            };

            reader.readAsDataURL(selectedFile);
        });
    }

    if (adminActionModalBackdrop) {
        adminActionModalBackdrop.addEventListener("click", function (event) {
            if (event.target === adminActionModalBackdrop) {
                closeAdminActionModal(true);
            }
        });
    }

    adminActionModalCancelButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            closeAdminActionModal(true);
        });
    });

    if (adminActionModalConfirm) {
        adminActionModalConfirm.addEventListener("click", function () {
            var onConfirmCallback = adminActionModalState.onConfirm;
            var onCloseCallback = adminActionModalState.onClose;
            var quantityValue = 1;

            if (adminActionModalState.quantityRequired && adminActionModalQuantityInput) {
                var parsedQuantity = Number.parseInt(String(adminActionModalQuantityInput.value || "1"), 10);
                var minQuantity = Number.parseInt(String(adminActionModalQuantityInput.min || "1"), 10);
                var maxQuantity = Number.parseInt(String(adminActionModalQuantityInput.max || "200"), 10);

                if (!Number.isFinite(minQuantity) || minQuantity < 1) {
                    minQuantity = 1;
                }

                if (!Number.isFinite(maxQuantity) || maxQuantity < minQuantity) {
                    maxQuantity = 200;
                }

                if (!Number.isFinite(parsedQuantity)) {
                    parsedQuantity = minQuantity;
                }

                parsedQuantity = Math.max(minQuantity, Math.min(maxQuantity, parsedQuantity));
                adminActionModalQuantityInput.value = String(parsedQuantity);
                quantityValue = parsedQuantity;
            }

            closeAdminActionModal(false);

            if (typeof onConfirmCallback === "function") {
                onConfirmCallback(quantityValue);
            }

            if (typeof onCloseCallback === "function") {
                onCloseCallback();
            }
        });
    }

    if (adminActionModalQuantityInput) {
        adminActionModalQuantityInput.addEventListener("keydown", function (event) {
            if (event.key === "Enter" && adminActionModalConfirm && !adminActionModalConfirm.disabled) {
                event.preventDefault();
                adminActionModalConfirm.click();
            }
        });
    }

    adminEquipmentAddButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            var form = button.closest("form");
            if (!form) {
                return;
            }

            var quantityInput = form.querySelector('input[name="quantity"]');
            var modelName = String(button.getAttribute("data-model") || "this model");

            openAdminActionModal({
                title: "Add Equipment Quantity",
                message: "How many units do you want to add for " + modelName + "? Removed IDs will be reused first.",
                confirmLabel: "Add",
                quantityRequired: true,
                quantityMin: 1,
                quantityMax: 200,
                quantityInitial: 1,
                onConfirm: function (quantityValue) {
                    if (quantityInput) {
                        quantityInput.value = String(quantityValue);
                    }

                    form.submit();
                }
            });
        });
    });

    adminEquipmentRemoveButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            var form = button.closest("form");
            if (!form) {
                return;
            }

            var willArchive = button.getAttribute("data-will-archive") === "true";
            var unitId = String(button.getAttribute("data-unit-id") || "this unit");
            var modelName = String(button.getAttribute("data-model") || "the selected model");
            var confirmMessage = willArchive
                ? "Removing the last quantity will archive the featured product and all of its equipment units. Continue?"
                : "Remove " + unitId + " from active inventory?";
            var title = willArchive ? "Archive Product Warning" : "Remove Equipment Unit";
            var description = willArchive
                ? modelName + " only has one active unit left. Removing it will archive the featured product and all related inventory units."
                : confirmMessage;

            openAdminActionModal({
                title: title,
                message: description,
                confirmLabel: willArchive ? "Archive Product" : "Remove Unit",
                onConfirm: function () {
                    form.submit();
                }
            });
        });
    });

    adminEquipmentStatusDeleteButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            if (button.disabled) {
                return;
            }

            event.preventDefault();

            var form = button.closest("form");
            if (!form) {
                return;
            }

            var statusLabel = String(button.getAttribute("data-status-label") || "this status");

            openAdminActionModal({
                title: "Remove Status",
                message: "Remove status \"" + statusLabel + "\"? Equipment using this status will be reassigned automatically.",
                confirmLabel: "Remove Status",
                onConfirm: function () {
                    form.submit();
                }
            });
        });
    });

    if (shouldOpenAdminUsersCreateModal) {
        openAdminUsersCreateModal();
    }

    if (shouldOpenAdminEquipmentArchiveModal) {
        openAdminEquipmentArchiveModal();
    }

    if (shouldOpenAdminEquipmentStatusModal) {
        openAdminEquipmentStatusModal();
    }

    document.addEventListener("keydown", function (event) {
        var reasonBackdrop = adminBookingReviewBackdrop && !adminBookingReviewBackdrop.hidden
            ? adminBookingReviewBackdrop
            : (adminBookingCancelBackdrop && !adminBookingCancelBackdrop.hidden ? adminBookingCancelBackdrop : null);
        if (reasonBackdrop && event.key === 'Tab') {
            var focusable = Array.from(reasonBackdrop.querySelectorAll('button, select, textarea, input, a[href]')).filter(function (node) {
                return !node.disabled && node.getClientRects().length > 0 && node.tabIndex >= 0;
            });
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (first && (!reasonBackdrop.contains(document.activeElement) || (event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
                event.preventDefault();
                (event.shiftKey ? last : first).focus();
            }
        }
        if (event.key !== "Escape") { return; }
        if (adminBookingReviewBackdrop && !adminBookingReviewBackdrop.hidden) {
            closeAdminBookingReviewModal();
            return;
        }

        if (adminUsersCreateBackdrop && !adminUsersCreateBackdrop.hidden) {
            closeAdminUsersCreateModal();
        }

        if (adminEquipmentArchiveBackdrop && !adminEquipmentArchiveBackdrop.hidden) {
            closeAdminEquipmentArchiveModal();
        }

        if (adminEquipmentStatusBackdrop && !adminEquipmentStatusBackdrop.hidden) {
            closeAdminEquipmentStatusModal();
        }

        if (adminActionModalBackdrop && !adminActionModalBackdrop.hidden) {
            closeAdminActionModal(true);
        }

        if (adminBookingDeliveryBackdrop && !adminBookingDeliveryBackdrop.hidden) {
            if (!isSubmittingAdminBookingDelivery) {
                closeAdminBookingDeliveryModal();
            }
            return;
        }

        if (adminBookingCancelBackdrop && !adminBookingCancelBackdrop.hidden) {
            closeAdminBookingCancelModal();
            return;
        }

        if (adminBookingDetailBackdrop && !adminBookingDetailBackdrop.hidden) {
            closeAdminBookingDetail();
        }
    });

    detailGalleries.forEach(function (gallery) {
        setupDetailGallery(gallery);
    });

    packageSlideshows.forEach(function (slideshow) {
        var controller = setupPackageSlideshow(slideshow);

        if (controller) {
            packageSlideshowControllers.push(controller);
        }
    });

    if (packageSlideshowControllers.length) {
        document.addEventListener("visibilitychange", function () {
            packageSlideshowControllers.forEach(function (controller) {
                if (document.hidden) {
                    controller.stop();
                    return;
                }

                controller.start();
            });
        });
    }

    var profileToggleButtons = document.querySelectorAll("[data-profile-toggle]");
    var profileCancelButtons = document.querySelectorAll("[data-profile-cancel]");
    var cancelModal = document.getElementById("profile-cancel-modal");
    var openCancelModalButtons = document.querySelectorAll("[data-profile-open-cancel-modal]");
    var closeCancelModalButtons = document.querySelectorAll("[data-profile-close-cancel-modal]");

    function hideEditor(editorId) {
        var target = document.getElementById(editorId);

        if (!target) {
            return;
        }

        target.hidden = true;
    }

    function toggleEditor(editorId) {
        var target = document.getElementById(editorId);

        if (!target) {
            return;
        }

        target.hidden = !target.hidden;
    }

    profileToggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var targetId = button.getAttribute("data-profile-toggle");

            if (!targetId) {
                return;
            }

            toggleEditor(targetId);
        });
    });

    profileCancelButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var targetId = button.getAttribute("data-profile-cancel");

            if (!targetId) {
                return;
            }

            hideEditor(targetId);
        });
    });

    function openCancelModal() {
        if (!cancelModal) {
            return;
        }

        cancelModal.hidden = false;
    }

    function closeCancelModal() {
        if (!cancelModal) {
            return;
        }

        cancelModal.hidden = true;
    }

    openCancelModalButtons.forEach(function (button) {
        button.addEventListener("click", openCancelModal);
    });

    closeCancelModalButtons.forEach(function (button) {
        button.addEventListener("click", closeCancelModal);
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeCancelModal();
        }
    });

    var termsToggleButtons = document.querySelectorAll("[data-terms-toggle]");

    termsToggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var contentId = button.getAttribute("aria-controls");
            var content = contentId ? document.getElementById(contentId) : null;
            var showLabel = button.getAttribute("data-label-show") || "Show Full Terms and Conditions";
            var hideLabel = button.getAttribute("data-label-hide") || "Hide Full Terms and Conditions";
            var isExpanded = button.getAttribute("aria-expanded") === "true";
            var nextExpanded = !isExpanded;

            button.setAttribute("aria-expanded", nextExpanded ? "true" : "false");
            button.textContent = nextExpanded ? hideLabel : showLabel;

            if (content) {
                content.hidden = !nextExpanded;
            }
        });
    });

    var cartStorageKey = "creaty_cart_v1";
    var bookingStorageKey = "creaty_booking_v1";
    var serviceBookingStorageKey = "creaty_service_booking_v1";

    function loadJsonStorage(storageKey, fallbackValue) {
        try {
            var raw = window.localStorage.getItem(storageKey);
            if (!raw) {
                return fallbackValue;
            }

            return JSON.parse(raw);
        } catch (error) {
            return fallbackValue;
        }
    }

    function saveJsonStorage(storageKey, value) {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(value));
        } catch (error) {
            // Keep UI usable even if localStorage quota is exceeded.
        }
    }

    function parseMoney(value) {
        var normalized = String(value || "").replace(/[^0-9.]/g, "");
        var amount = Number.parseFloat(normalized);

        if (!Number.isFinite(amount)) {
            return 0;
        }

        return amount;
    }

    function formatMoney(value) {
        var safeValue = Number.isFinite(value) ? value : 0;

        return "P " + safeValue.toFixed(2);
    }

    function getCartItems() {
        var parsed = loadJsonStorage(cartStorageKey, []);

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map(function (item) {
                if (!item || typeof item !== "object") {
                    return null;
                }

                var qty = Number.parseInt(item.qty, 10);
                var days = Number.parseInt(item.days, 10);

                return {
                    id: String(item.id || ""),
                    type: String(item.type || "item"),
                    name: String(item.name || "Unnamed Item"),
                    copy: String(item.copy || ""),
                    image: String(item.image || ""),
                    price: parseMoney(item.price),
                    qty: Number.isFinite(qty) && qty > 0 ? qty : 1,
                    days: Number.isFinite(days) && days > 0 ? days : 1
                };
            })
            .filter(function (item) {
                return item && item.id;
            });
    }

    function normalizeServiceBookingItem(item) {
        if (!item || typeof item !== "object") {
            return null;
        }

        var itemId = String(item.id || "").trim();
        if (!itemId) {
            return null;
        }

        var durationUnitRaw = String(item.durationUnit || item.duration_unit || "").trim().toLowerCase();
        var durationUnit = durationUnitRaw === "days" || durationUnitRaw === "day"
            ? "days"
            : "hours";
        var durationValue = Number.parseInt(item.durationValue || item.duration_value || "", 10);

        if (!Number.isFinite(durationValue) || durationValue < 1) {
            var legacyMinHours = Number.parseInt(item.durationMinHours || item.duration_min_hours || 1, 10);
            var legacyMaxHours = Number.parseInt(item.durationMaxHours || item.duration_max_hours || legacyMinHours, 10);

            if (!Number.isFinite(legacyMinHours) || legacyMinHours < 1) {
                legacyMinHours = 1;
            }

            if (!Number.isFinite(legacyMaxHours) || legacyMaxHours < legacyMinHours) {
                legacyMaxHours = legacyMinHours;
            }

            durationUnit = "hours";
            durationValue = legacyMaxHours;
        }

        if (durationUnit === "days") {
            durationValue = Math.max(1, Math.min(14, durationValue));
        } else {
            durationUnit = "hours";
            durationValue = Math.max(1, Math.min(24, durationValue));
        }

        return {
            id: itemId,
            type: "service-package",
            servicePackageKey: String(item.servicePackageKey || item.service_package_key || "").trim().toLowerCase(),
            name: String(item.name || "Service Package"),
            copy: String(item.copy || ""),
            image: String(item.image || ""),
            price: parseMoney(item.price),
            qty: 1,
            days: 1,
            durationUnit: durationUnit,
            durationValue: durationValue
        };
    }

    function getServiceBookingItem() {
        return normalizeServiceBookingItem(loadJsonStorage(serviceBookingStorageKey, null));
    }

    function saveServiceBookingItem(item) {
        var normalizedItem = normalizeServiceBookingItem(item);

        if (!normalizedItem) {
            clearServiceBookingItem();
            return;
        }

        saveJsonStorage(serviceBookingStorageKey, normalizedItem);
    }

    function clearServiceBookingItem() {
        try {
            window.localStorage.removeItem(serviceBookingStorageKey);
        } catch (error) {
            saveJsonStorage(serviceBookingStorageKey, null);
        }
    }

    function saveCartItems(items) {
        saveJsonStorage(cartStorageKey, items);
    }

    function getCartCount(items) {
        var source = Array.isArray(items) ? items : getCartItems();

        return source.reduce(function (total, item) {
            return total + (Number.isFinite(item.qty) ? item.qty : 0);
        }, 0);
    }

    function syncCartCountBadges(items) {
        var count = getCartCount(items);
        var badges = document.querySelectorAll(".cart-count:not(.topbar-notification-count)");

        badges.forEach(function (badge) {
            badge.textContent = String(count);
        });
    }

    function showCartToast(message) {
        var toast = document.getElementById("cart-toast");
        if (!toast) {
            toast = document.createElement("div");
            toast.id = "cart-toast";
            toast.className = "cart-toast";
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.add("is-visible");

        window.setTimeout(function () {
            toast.classList.remove("is-visible");
        }, 1800);
    }

    function addOrUpdateCartItem(nextItem) {
        var items = getCartItems();
        var existingIndex = items.findIndex(function (item) {
            return item.id === nextItem.id;
        });

        if (existingIndex >= 0) {
            items[existingIndex].qty += 1;
        } else {
            items.push(nextItem);
        }

        saveCartItems(items);
        syncCartCountBadges(items);
    }

    function initializeAddToCartButtons() {
        var buttons = document.querySelectorAll("[data-add-cart]");

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                var loginUrl = button.getAttribute("data-login-url");
                if (loginUrl) {
                    window.location.href = loginUrl;
                    return;
                }

                var itemId = button.getAttribute("data-item-id");
                if (!itemId) {
                    return;
                }

                var normalizedItemId = String(itemId || "").toLowerCase().trim();
                var productKey = "";

                if (normalizedItemId.indexOf("camera-") === 0) {
                    productKey = normalizedItemId.slice(7);
                }

                addOrUpdateCartItem({
                    id: itemId,
                    type: button.getAttribute("data-item-type") || "item",
                    productKey: productKey,
                    name: button.getAttribute("data-item-name") || "Unnamed Item",
                    copy: button.getAttribute("data-item-copy") || "",
                    image: button.getAttribute("data-item-image") || "",
                    price: parseMoney(button.getAttribute("data-item-price") || "0"),
                    qty: 1,
                    days: 1
                });

                showCartToast("Added to reservation");
            });
        });
    }

    function initializeServicePurchaseButtons() {
        var buttons = document.querySelectorAll("[data-service-purchase]");

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                var loginUrl = button.getAttribute("data-login-url");
                if (loginUrl) {
                    window.location.href = loginUrl;
                    return;
                }

                var itemId = String(button.getAttribute("data-item-id") || "").trim();
                if (!itemId) {
                    return;
                }

                var fallbackPackageKey = itemId.toLowerCase().indexOf("service-") === 0
                    ? itemId.toLowerCase().slice(8)
                    : "";

                saveServiceBookingItem({
                    id: itemId,
                    servicePackageKey: button.getAttribute("data-service-package-key") || fallbackPackageKey,
                    name: button.getAttribute("data-item-name") || "Service Package",
                    copy: button.getAttribute("data-item-copy") || "",
                    image: button.getAttribute("data-item-image") || "",
                    price: parseMoney(button.getAttribute("data-item-price") || "0"),
                    durationUnit: button.getAttribute("data-duration-unit") || "",
                    durationValue: button.getAttribute("data-duration-value") || "",
                    durationMinHours: button.getAttribute("data-duration-min-hours") || "",
                    durationMaxHours: button.getAttribute("data-duration-max-hours") || button.getAttribute("data-duration-min-hours") || ""
                });

                var configuredUrl = String(button.getAttribute("data-service-cart-url") || "").trim();
                var defaultUrl = (window.__creatyAssetBase ? String(window.__creatyAssetBase) : "/") + "customer-cart/?view=services-cart";
                window.location.href = configuredUrl || defaultUrl;
            });
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function initializeCustomerNotificationCenter() {
        if (document.querySelector("[data-cart-items-panel]")) {
            return;
        }

        var customerNotificationTrigger = document.querySelector("[data-customer-notification-trigger]");
        var customerNotificationCountBadges = document.querySelectorAll("[data-customer-notification-count]");
        var customerNotificationModal = document.querySelector("[data-customer-notification-modal]");
        var customerNotificationModalCloseButtons = customerNotificationModal ? customerNotificationModal.querySelectorAll("[data-customer-notification-close]") : [];
        var customerNotificationList = customerNotificationModal ? customerNotificationModal.querySelector("[data-customer-notification-list]") : null;
        var customerNotificationEmpty = customerNotificationModal ? customerNotificationModal.querySelector("[data-customer-notification-empty]") : null;

        if (!customerNotificationTrigger || !customerNotificationModal || !customerNotificationList) {
            return;
        }

        var customerNotificationLiveEndpoint = typeof window.__creatyCustomerNotificationLiveEndpoint === "string"
            ? String(window.__creatyCustomerNotificationLiveEndpoint || "")
            : "";
        var customerNotificationMarkReadEndpoint = typeof window.__creatyCustomerNotificationMarkReadEndpoint === "string"
            ? String(window.__creatyCustomerNotificationMarkReadEndpoint || "")
            : "";
        var customerCartPath = typeof window.__creatyCustomerCartPath === "string"
            ? String(window.__creatyCustomerCartPath || "")
            : "";
        var assetBase = typeof window.__creatyAssetBase === "string"
            ? String(window.__creatyAssetBase || "")
            : "";
        var customerNotifications = Array.isArray(window.__creatyCustomerNotifications)
            ? window.__creatyCustomerNotifications.slice()
            : [];
        var customerNotificationUnreadCountParsed = Number.parseInt(String(window.__creatyCustomerNotificationUnreadCount || "0"), 10);
        var customerNotificationUnreadCount = Number.isFinite(customerNotificationUnreadCountParsed) && customerNotificationUnreadCountParsed > 0
            ? customerNotificationUnreadCountParsed
            : 0;
        var customerLivePollIntervalMs = 4000;
        var customerLivePollTimerId = null;
        var customerLivePollInFlight = false;

        function normalizeCustomerNotificationCount(value) {
            var parsed = Number.parseInt(String(value || "0"), 10);

            if (!Number.isFinite(parsed) || parsed < 0) {
                return 0;
            }

            return parsed;
        }

        function normalizeCustomerNotificationRecord(record) {
            if (!record || typeof record !== "object") {
                return null;
            }

            var id = String(record.id || "").trim();

            if (!id) {
                return null;
            }

            return {
                id: id,
                type: String(record.type || "order-status").trim() || "order-status",
                orderId: String(record.orderId || record.order_id || "").trim().toUpperCase(),
                statusToken: String(record.statusToken || record.status_token || "").trim().toLowerCase(),
                title: String(record.title || "Notification").trim() || "Notification",
                summary: String(record.summary || "").trim(),
                targetView: String(record.targetView || record.target_view || "order-status").trim().toLowerCase() === "order-status"
                    ? "order-status"
                    : "order-status",
                isRead: String(record.isRead || record.is_read || "0") === "1" || Boolean(record.isRead || record.is_read),
                createdAt: String(record.createdAt || record.created_at || "").trim(),
                createdAtLabel: String(record.createdAtLabel || record.created_at_label || "").trim()
            };
        }

        function saveCustomerNotifications(nextNotifications) {
            if (!Array.isArray(nextNotifications)) {
                customerNotifications = [];
                return;
            }

            customerNotifications = nextNotifications
                .map(function (notification) {
                    return normalizeCustomerNotificationRecord(notification);
                })
                .filter(function (notification) {
                    return Boolean(notification);
                });
        }

        function updateCustomerNotificationEmptyState() {
            if (!customerNotificationEmpty) {
                return;
            }

            customerNotificationEmpty.hidden = customerNotifications.length > 0;
        }

        function setCustomerNotificationBadgeCount(nextCount) {
            var normalizedCount = normalizeCustomerNotificationCount(nextCount);
            customerNotificationUnreadCount = normalizedCount;

            customerNotificationCountBadges.forEach(function (badge) {
                badge.textContent = String(normalizedCount);
            });
        }

        function getCustomerNotificationTimeLabel(notification) {
            if (!notification || typeof notification !== "object") {
                return "";
            }

            var createdAtLabel = String(notification.createdAtLabel || "").trim();

            if (createdAtLabel) {
                return createdAtLabel;
            }

            var createdAt = String(notification.createdAt || "").trim();

            if (!createdAt) {
                return "";
            }

            var timestamp = Date.parse(createdAt);

            if (!Number.isFinite(timestamp)) {
                return createdAt;
            }

            return new Date(timestamp).toLocaleString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
                hour: "numeric",
                minute: "2-digit"
            });
        }

        function renderCustomerNotificationList() {
            if (!customerNotificationList) {
                return;
            }

            customerNotificationList.innerHTML = "";
            updateCustomerNotificationEmptyState();

            customerNotifications.forEach(function (notification) {
                var item = document.createElement("button");
                item.type = "button";
                item.className = "cart-customer-notification-item" + (notification.isRead ? " is-read" : " is-unread");
                item.setAttribute("role", "listitem");
                item.setAttribute("data-customer-notification-item", "true");
                item.setAttribute("data-customer-notification-id", String(notification.id || ""));
                item.setAttribute("data-customer-notification-type", String(notification.type || "order-status"));
                item.setAttribute("data-customer-notification-order-id", String(notification.orderId || ""));
                item.setAttribute("data-customer-notification-target-view", String(notification.targetView || "order-status"));

                var titleValue = String(notification.title || "Notification").trim() || "Notification";
                var summaryValue = String(notification.summary || "").trim();
                var timeLabel = getCustomerNotificationTimeLabel(notification);

                var head = document.createElement("div");
                head.className = "cart-customer-notification-item-head";

                var title = document.createElement("p");
                title.className = "cart-customer-notification-title";
                title.textContent = titleValue;

                var time = document.createElement("span");
                time.className = "cart-customer-notification-time";
                time.textContent = timeLabel || "";

                head.appendChild(title);
                head.appendChild(time);
                item.appendChild(head);

                if (summaryValue) {
                    var summary = document.createElement("p");
                    summary.className = "cart-customer-notification-summary";
                    summary.textContent = summaryValue;
                    item.appendChild(summary);
                }

                customerNotificationList.appendChild(item);
            });
        }

        function closeCustomerNotificationModal() {
            if (!customerNotificationModal) {
                return;
            }

            customerNotificationModal.hidden = true;
        }

        function openCustomerNotificationModal() {
            if (!customerNotificationModal) {
                return;
            }

            renderCustomerNotificationList();
            customerNotificationModal.hidden = false;
        }

        function setCustomerNotificationReadState(notificationId, shouldRead) {
            var targetId = String(notificationId || "").trim();

            if (!targetId) {
                return;
            }

            customerNotifications = customerNotifications.map(function (notification) {
                if (!notification || notification.id !== targetId) {
                    return notification;
                }

                return Object.assign({}, notification, {
                    isRead: Boolean(shouldRead)
                });
            });

            var unreadCount = customerNotifications.reduce(function (total, notification) {
                if (!notification || notification.isRead) {
                    return total;
                }

                return total + 1;
            }, 0);

            setCustomerNotificationBadgeCount(unreadCount);
            renderCustomerNotificationList();
        }

        function markCustomerNotificationAsRead(notificationId) {
            var targetId = String(notificationId || "").trim();

            if (!targetId) {
                return Promise.resolve();
            }

            if (!customerNotificationMarkReadEndpoint) {
                setCustomerNotificationReadState(targetId, true);
                return Promise.resolve();
            }

            return window.fetch(customerNotificationMarkReadEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    notificationId: targetId
                })
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false
                    };
                }).then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            }).then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    setCustomerNotificationReadState(targetId, true);
                    return;
                }

                setCustomerNotificationReadState(targetId, true);

                if (typeof result.payload.unreadCount !== "undefined") {
                    setCustomerNotificationBadgeCount(result.payload.unreadCount);
                }
            }).catch(function () {
                setCustomerNotificationReadState(targetId, true);
            });
        }

        function markCustomerOrderNotificationsAsRead() {
            var targetType = "order-status";
            var unreadOrderNotificationIds = customerNotifications
                .filter(function (notification) {
                    if (!notification || typeof notification !== "object") {
                        return false;
                    }

                    return String(notification.type || "").trim().toLowerCase() === targetType && !notification.isRead;
                })
                .map(function (notification) {
                    return String(notification.id || "").trim();
                })
                .filter(function (notificationId) {
                    return notificationId !== "";
                });

            if (!unreadOrderNotificationIds.length) {
                return Promise.resolve();
            }

            customerNotifications = customerNotifications.map(function (notification) {
                if (!notification || typeof notification !== "object") {
                    return notification;
                }

                if (String(notification.type || "").trim().toLowerCase() !== targetType) {
                    return notification;
                }

                return Object.assign({}, notification, {
                    isRead: true
                });
            });

            var unreadCount = customerNotifications.reduce(function (total, notification) {
                if (!notification || notification.isRead) {
                    return total;
                }

                return total + 1;
            }, 0);

            setCustomerNotificationBadgeCount(unreadCount);
            renderCustomerNotificationList();

            if (!customerNotificationMarkReadEndpoint) {
                return Promise.resolve();
            }

            return window.fetch(customerNotificationMarkReadEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    markAllOrderNotifications: true
                })
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false
                    };
                }).then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            }).then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    return;
                }

                if (typeof result.payload.unreadCount !== "undefined") {
                    setCustomerNotificationBadgeCount(result.payload.unreadCount);
                }
            }).catch(function () {
                // Keep optimistic UI state and let live updates reconcile.
            });
        }

        function buildOrderStatusUrl(orderId) {
            var basePath = String(customerCartPath || "").trim();

            if (!basePath) {
                basePath = (assetBase ? assetBase : "/") + "customer-cart/";
            }

            try {
                var targetUrl = new URL(basePath, window.location.href);
                targetUrl.searchParams.set("view", "order-status");

                if (orderId) {
                    targetUrl.searchParams.set("focusOrder", String(orderId).toUpperCase());
                }

                return targetUrl.toString();
            } catch (error) {
                var separator = basePath.indexOf("?") === -1 ? "?" : "&";
                var fallback = basePath + separator + "view=order-status";

                if (orderId) {
                    fallback += "&focusOrder=" + encodeURIComponent(String(orderId).toUpperCase());
                }

                return fallback;
            }
        }

        function applyCustomerLivePayload(payload) {
            if (!payload || typeof payload !== "object") {
                return;
            }

            if (Array.isArray(payload.notifications)) {
                saveCustomerNotifications(payload.notifications);
            }

            if (typeof payload.unreadCount !== "undefined") {
                setCustomerNotificationBadgeCount(payload.unreadCount);
            }

            renderCustomerNotificationList();
        }

        function pollCustomerLiveUpdates() {
            if (!customerNotificationLiveEndpoint || customerLivePollInFlight) {
                return;
            }

            customerLivePollInFlight = true;

            window.fetch(customerNotificationLiveEndpoint, {
                headers: {
                    Accept: "application/json"
                }
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false
                    };
                }).then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            }).then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    return;
                }

                applyCustomerLivePayload(result.payload);
            }).catch(function () {
                // Ignore transient polling errors.
            }).finally(function () {
                customerLivePollInFlight = false;
            });
        }

        function startCustomerLiveUpdates() {
            if (customerLivePollTimerId !== null || !customerNotificationLiveEndpoint) {
                return;
            }

            customerLivePollTimerId = window.setInterval(pollCustomerLiveUpdates, customerLivePollIntervalMs);
        }

        if (!customerNotificationUnreadCount) {
            customerNotificationUnreadCount = customerNotifications.reduce(function (total, notification) {
                if (!notification || notification.isRead) {
                    return total;
                }

                return total + 1;
            }, 0);
        }

        saveCustomerNotifications(customerNotifications);
        setCustomerNotificationBadgeCount(customerNotificationUnreadCount);
        renderCustomerNotificationList();

        customerNotificationTrigger.addEventListener("click", function () {
            openCustomerNotificationModal();
            pollCustomerLiveUpdates();
        });

        customerNotificationModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeCustomerNotificationModal();
            });
        });

        customerNotificationList.addEventListener("click", function (event) {
            var notificationItem = event.target.closest("[data-customer-notification-item]");

            if (!notificationItem) {
                return;
            }

            var notificationId = String(notificationItem.getAttribute("data-customer-notification-id") || "").trim();
            var notificationType = String(notificationItem.getAttribute("data-customer-notification-type") || "order-status").trim().toLowerCase();
            var targetView = String(notificationItem.getAttribute("data-customer-notification-target-view") || "order-status").trim().toLowerCase();
            var orderId = String(notificationItem.getAttribute("data-customer-notification-order-id") || "").trim();

            closeCustomerNotificationModal();

            if (targetView === "order-status") {
                var targetUrl = buildOrderStatusUrl(orderId);

                if (notificationType === "order-status") {
                    markCustomerOrderNotificationsAsRead().finally(function () {
                        window.location.href = targetUrl;
                    });
                    return;
                }

                markCustomerNotificationAsRead(notificationId).finally(function () {
                    window.location.href = targetUrl;
                });
                return;
            }

            if (notificationType === "order-status") {
                markCustomerOrderNotificationsAsRead();
                return;
            }

            markCustomerNotificationAsRead(notificationId);
        });

        document.addEventListener("visibilitychange", function () {
            if (!document.hidden) {
                pollCustomerLiveUpdates();
            }
        });

        pollCustomerLiveUpdates();
        startCustomerLiveUpdates();
    }

    function initializeCartPage() {
        var panel = document.querySelector("[data-cart-items-panel]");
        if (!panel) {
            return;
        }

        var emptyMessage = panel.querySelector("[data-cart-empty-message]");
        var totalNode = document.querySelector("[data-cart-total]");
        var breakdownNode = document.querySelector("[data-cart-breakdown]");
        var bookingCard = document.querySelector("[data-cart-booking]");
        var bookingNote = document.querySelector("[data-cart-booking-note]");
        var bookingNoteLink = document.querySelector("[data-cart-booking-note-link]");
        var bookingPaymentNote = document.querySelector("[data-cart-booking-payment-note]");
        var confirmButton = bookingCard ? bookingCard.querySelector(".cart-confirm-button") : null;
        var paymentSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='paymentMethod']") : null;
        var deliveryOnlyBlock = bookingCard ? bookingCard.querySelector("[data-delivery-only-block]") : null;
        var courierSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='courier']") : null;
        var courierRow = courierSelect ? courierSelect.closest(".cart-form-line") : null;
        var placeSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='place']") : null;
        var placeFieldRow = placeSelect ? placeSelect.closest("[data-booking-place-row]") : null;
        var placeFieldLabel = placeFieldRow ? placeFieldRow.querySelector("span") : null;
        var eventPlaceInput = bookingCard ? bookingCard.querySelector("[data-booking-event-place-input]") : null;
        var receiveDateInput = bookingCard ? bookingCard.querySelector("[data-booking-field='receiveDate']") : null;
        var receiveDateDisplay = bookingCard ? bookingCard.querySelector("[data-receive-date-display]") : null;
        var receiveDateCalendar = bookingCard ? bookingCard.querySelector("[data-receive-date-calendar]") : null;
        var receiveDateCalendarTitle = bookingCard ? bookingCard.querySelector("[data-receive-calendar-title]") : null;
        var receiveDateCalendarGrid = bookingCard ? bookingCard.querySelector("[data-receive-calendar-grid]") : null;
        var receiveDateCalendarNote = bookingCard ? bookingCard.querySelector("[data-receive-calendar-note]") : null;
        var receiveDateCalendarNavButtons = bookingCard ? bookingCard.querySelectorAll("[data-receive-calendar-nav]") : [];
        var returnDateInput = bookingCard ? bookingCard.querySelector("[data-booking-field='returnDate']") : null;
        var receiveTimeSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='receiveTime']") : null;
        var returnTimeSelect = bookingCard ? bookingCard.querySelector("[data-booking-field='returnTime']") : null;
        var lateNote = bookingCard ? bookingCard.querySelector(".cart-late-note") : null;
        var receiveMethodInputs = bookingCard ? bookingCard.querySelectorAll("input[name='receivingMethod']") : [];
        var returnMethodInputs = bookingCard ? bookingCard.querySelectorAll("input[name='returningMethod']") : [];
        var methodsRow = bookingCard ? bookingCard.querySelector(".cart-methods-row") : null;
        var uploadInputs = bookingCard ? bookingCard.querySelectorAll("input[type='file'][data-booking-field]") : [];
        var equipmentReceiveTimeOptions = receiveTimeSelect
            ? Array.prototype.map.call(receiveTimeSelect.options, function (option) {
                return {
                    value: String(option.value || ""),
                    label: String(option.textContent || "")
                };
            })
            : [];
        var equipmentReturnTimeOptions = returnTimeSelect
            ? Array.prototype.map.call(returnTimeSelect.options, function (option) {
                return {
                    value: String(option.value || ""),
                    label: String(option.textContent || "")
                };
            })
            : [];
        var cartHeaderNav = document.querySelector("header.site-header .section-nav");
        var cartNavButtons = document.querySelectorAll("[data-cart-nav]");
        var cartSidebar = document.querySelector("[data-cart-sidebar]");
        var cartLayout = document.querySelector(".cart-layout");
        var cartMainHeading = document.querySelector("[data-cart-main-heading]");
        var cartViewPanels = {
            cart: document.querySelector("[data-cart-view='cart']"),
            orderStatus: document.querySelector("[data-cart-view='order-status']"),
            history: document.querySelector("[data-cart-view='history']")
        };
        var orderStatusList = document.querySelector("[data-cart-orders-list]");
        var orderStatusEmpty = document.querySelector("[data-cart-orders-empty]");
        var historyList = document.querySelector("[data-cart-history-list]");
        var historyEmpty = document.querySelector("[data-cart-history-empty]");
        var customerNotificationTrigger = document.querySelector("[data-customer-notification-trigger]");
        var customerNotificationCountBadges = document.querySelectorAll("[data-customer-notification-count]");
        var customerNotificationModal = document.querySelector("[data-customer-notification-modal]");
        var customerNotificationModalCloseButtons = customerNotificationModal ? customerNotificationModal.querySelectorAll("[data-customer-notification-close]") : [];
        var customerNotificationList = customerNotificationModal ? customerNotificationModal.querySelector("[data-customer-notification-list]") : null;
        var customerNotificationEmpty = customerNotificationModal ? customerNotificationModal.querySelector("[data-customer-notification-empty]") : null;
        var orderSubmitEndpoint = typeof window.__creatyCustomerOrderSubmitEndpoint === "string"
            ? String(window.__creatyCustomerOrderSubmitEndpoint || "")
            : "";
        var orderCancelEndpoint = typeof window.__creatyCustomerOrderCancelEndpoint === "string"
            ? String(window.__creatyCustomerOrderCancelEndpoint || "")
            : "";
        var orderReceiptUploadEndpoint = typeof window.__creatyCustomerOrderReceiptUploadEndpoint === "string"
            ? String(window.__creatyCustomerOrderReceiptUploadEndpoint || "")
            : "";
        var orderDeliveryReceiptUploadEndpoint = typeof window.__creatyCustomerOrderDeliveryReceiptUploadEndpoint === "string"
            ? String(window.__creatyCustomerOrderDeliveryReceiptUploadEndpoint || "")
            : "";
        var assetBase = typeof window.__creatyAssetBase === "string"
            ? String(window.__creatyAssetBase || "")
            : "";
        var customerNotificationLiveEndpoint = typeof window.__creatyCustomerNotificationLiveEndpoint === "string"
            ? String(window.__creatyCustomerNotificationLiveEndpoint || "")
            : "";
        var customerNotificationMarkReadEndpoint = typeof window.__creatyCustomerNotificationMarkReadEndpoint === "string"
            ? String(window.__creatyCustomerNotificationMarkReadEndpoint || "")
            : "";
        var customerInitialViewRaw = String(window.__creatyCustomerInitialView || "").toLowerCase().trim();
        var customerInitialView = customerInitialViewRaw === "order-status" || customerInitialViewRaw === "services-cart" || customerInitialViewRaw === "history"
            ? customerInitialViewRaw
            : "cart";
        var cartCatalogSource = window.__creatyCartCatalog && typeof window.__creatyCartCatalog === "object"
            ? window.__creatyCartCatalog
            : {};
        var serverOrders = Array.isArray(window.__creatyCustomerOrders)
            ? window.__creatyCustomerOrders.slice()
            : [];
        var customerNotifications = Array.isArray(window.__creatyCustomerNotifications)
            ? window.__creatyCustomerNotifications.slice()
            : [];
        var bookingState = loadJsonStorage(bookingStorageKey, {});
        var unavailableModal = document.querySelector("[data-cart-unavailable-modal]");
        var unavailableModalMessage = unavailableModal ? unavailableModal.querySelector("[data-cart-unavailable-message]") : null;
        var unavailableModalConfirm = unavailableModal ? unavailableModal.querySelector("[data-cart-unavailable-confirm]") : null;
        var unavailableModalConfirmDefaultText = unavailableModalConfirm ? String(unavailableModalConfirm.textContent || "Remove and Continue") : "Remove and Continue";
        var unavailableModalCloseButtons = unavailableModal ? unavailableModal.querySelectorAll("[data-cart-unavailable-close]") : [];
        var orderCancelModal = document.querySelector("[data-cart-order-cancel-modal]");
        var orderCancelReasonInput = orderCancelModal ? orderCancelModal.querySelector("[data-cart-order-cancel-reason]") : null;
        var orderCancelError = orderCancelModal ? orderCancelModal.querySelector("[data-cart-order-cancel-error]") : null;
        var orderCancelConfirmButton = orderCancelModal ? orderCancelModal.querySelector("[data-cart-order-cancel-confirm]") : null;
        var orderCancelCloseButtons = orderCancelModal ? orderCancelModal.querySelectorAll("[data-cart-order-cancel-close]") : [];
        var refundProofModal = document.querySelector("[data-cart-refund-proof-modal]");
        var refundProofModalImage = refundProofModal ? refundProofModal.querySelector("[data-cart-refund-proof-image]") : null;
        var refundProofModalEmpty = refundProofModal ? refundProofModal.querySelector("[data-cart-refund-proof-empty]") : null;
        var refundProofModalCloseButtons = refundProofModal ? refundProofModal.querySelectorAll("[data-cart-refund-proof-close]") : [];
        var deliveryProofModal = document.querySelector("[data-cart-delivery-proof-modal]");
        var deliveryProofModalTitle = deliveryProofModal ? deliveryProofModal.querySelector("[data-cart-delivery-proof-title]") : null;
        var deliveryProofModalImage = deliveryProofModal ? deliveryProofModal.querySelector("[data-cart-delivery-proof-image]") : null;
        var deliveryProofModalEmpty = deliveryProofModal ? deliveryProofModal.querySelector("[data-cart-delivery-proof-empty]") : null;
        var deliveryProofModalCloseButtons = deliveryProofModal ? deliveryProofModal.querySelectorAll("[data-cart-delivery-proof-close]") : [];
        var deliveryUploadModal = document.querySelector("[data-cart-delivery-upload-modal]");
        var deliveryUploadModalCopy = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-copy]") : null;
        var deliveryUploadFileInput = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-file]") : null;
        var deliveryUploadFilename = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-filename]") : null;
        var deliveryUploadSelectButton = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-select]") : null;
        var deliveryUploadReferenceInput = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-reference]") : null;
        var deliveryUploadNotesInput = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-notes]") : null;
        var deliveryUploadMessage = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-message]") : null;
        var deliveryUploadSubmitButton = deliveryUploadModal ? deliveryUploadModal.querySelector("[data-cart-delivery-upload-submit]") : null;
        var deliveryUploadCloseButtons = deliveryUploadModal ? deliveryUploadModal.querySelectorAll("[data-cart-delivery-upload-close]") : [];
        var termsConsentWrap = document.querySelector("[data-cart-terms-consent]");
        var termsCheckbox = termsConsentWrap ? termsConsentWrap.querySelector("[data-cart-terms-checkbox]") : null;
        var termsOpenButton = document.querySelector("[data-cart-terms-open]");
        var termsModal = document.querySelector("[data-cart-terms-modal]");
        var termsModalScroll = termsModal ? termsModal.querySelector("[data-cart-terms-scroll]") : null;
        var termsModalAgreeButton = termsModal ? termsModal.querySelector("[data-cart-terms-agree]") : null;
        var termsModalCloseButtons = termsModal ? termsModal.querySelectorAll("[data-cart-terms-close]") : [];
        var gcashModal = document.querySelector("[data-cart-gcash-modal]");
        var gcashModalCloseButtons = gcashModal ? gcashModal.querySelectorAll("[data-cart-gcash-close]") : [];
        var gcashModalContinueButton = gcashModal ? gcashModal.querySelector("[data-cart-gcash-continue]") : null;
        var gcashModalQrImage = gcashModal ? gcashModal.querySelector("[data-cart-gcash-qr-image]") : null;
        var gcashModalQrEmpty = gcashModal ? gcashModal.querySelector("[data-cart-gcash-qr-empty]") : null;
        var gcashModalName = gcashModal ? gcashModal.querySelector("[data-cart-gcash-name]") : null;
        var gcashModalNumber = gcashModal ? gcashModal.querySelector("[data-cart-gcash-number]") : null;
        var gcashModalInstruction = gcashModal ? gcashModal.querySelector("[data-cart-gcash-instruction]") : null;
        var gcashReceiptBlock = gcashModal ? gcashModal.querySelector("[data-cart-gcash-receipt-block]") : null;
        var gcashCustomerInfoWrap = gcashModal ? gcashModal.querySelector("[data-cart-customer-gcash-info]") : null;
        var gcashCustomerNameValue = gcashModal ? gcashModal.querySelector("[data-cart-customer-gcash-name-value]") : null;
        var gcashCustomerNumberValue = gcashModal ? gcashModal.querySelector("[data-cart-customer-gcash-number-value]") : null;
        var gcashReceiptFileInput = gcashModal ? gcashModal.querySelector("[data-cart-gcash-receipt-file]") : null;
        var gcashReceiptSelectButton = gcashModal ? gcashModal.querySelector("[data-cart-gcash-receipt-select]") : null;
        var gcashReceiptFilename = gcashModal ? gcashModal.querySelector("[data-cart-gcash-receipt-filename]") : null;
        var gcashReceiptTimer = gcashModal ? gcashModal.querySelector("[data-cart-gcash-receipt-timer]") : null;
        var gcashUploadButton = gcashModal ? gcashModal.querySelector("[data-cart-gcash-upload]") : null;
        var gcashUploadMessage = gcashModal ? gcashModal.querySelector("[data-cart-gcash-upload-message]") : null;
        var gcashPaymentInfo = normalizeGcashPaymentInfo(window.__creatyGcashPaymentInfo);
        var customerGcashInfo = normalizeCustomerGcashInfo(window.__creatyCustomerGcashInfo);
        var customerPhone = typeof window.__creatyCustomerPhone === "string"
            ? String(window.__creatyCustomerPhone || "")
            : "";
        var customerAccountSettingsPath = typeof window.__creatyCustomerAccountSettingsPath === "string"
            ? String(window.__creatyCustomerAccountSettingsPath || "")
            : "";
        var isCustomerLoggedIn = window.__creatyCustomerLoggedIn === true;
        var paymentReceiptTimeoutSecondsDefault = 10 * 60;
        var paymentReceiptAutoCancelReason = "Failure to upload payment receipt.";
        var customerLivePollIntervalMs = 4000;
        var customerLivePollTimerId = null;
        var customerLivePollInFlight = false;
        var customerLiveLastOrdersSignature = typeof window.__creatyCustomerOrdersSignature === "string"
            ? String(window.__creatyCustomerOrdersSignature || "").trim()
            : "";
        var customerNotificationUnreadCountParsed = Number.parseInt(String(window.__creatyCustomerNotificationUnreadCount || "0"), 10);
        var customerNotificationUnreadCount = Number.isFinite(customerNotificationUnreadCountParsed) && customerNotificationUnreadCountParsed > 0
            ? customerNotificationUnreadCountParsed
            : 0;
        var activeCartView = "cart";
        var orderStatusFocusId = "";
        var activeOrderStatusEntryId = "";
        var activeCancelOrderId = "";
        var pendingGcashOrderRecord = null;
        var activeGcashUploadOrderId = "";
        var activeGcashModalMode = "";
        var isSubmittingPendingOrder = false;
        var isUploadingGcashReceipt = false;
        var activeDeliveryUploadOrderId = "";
        var isUploadingDeliveryReceipt = false;
        var receiptCountdownIntervalId = null;
        var isAutoCancelReloadQueued = false;
        var availableCartItemIdsSource = Array.isArray(window.__creatyCartAvailableItemIds) ? window.__creatyCartAvailableItemIds : [];
        var availableCartItemIdsSet = availableCartItemIdsSource.reduce(function (set, itemId) {
            if (typeof itemId === "string" && itemId) {
                set[itemId] = true;
            }

            return set;
        }, {});
        var hasAvailabilitySnapshot = Object.keys(availableCartItemIdsSet).length > 0;
        var receiveDateCalendarCursor = null;
        var receiveCalendarWeekdays = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
        var termsModalScrolledToEnd = false;
        var hasAcceptedTerms = String(bookingState.termsAccepted || "") === "1" || bookingState.termsAccepted === true;

        function isUnavailableCartItem(item) {
            if (!item || !item.id || !hasAvailabilitySnapshot) {
                return false;
            }

            return !availableCartItemIdsSet[item.id];
        }

        function getUnavailableCartItems(items) {
            var source = Array.isArray(items) ? items : getCartItems();

            return source.filter(function (item) {
                return isUnavailableCartItem(item);
            });
        }

        function removeUnavailableCartItems() {
            var items = getCartItems();
            var filteredItems = items.filter(function (item) {
                return !isUnavailableCartItem(item);
            });

            saveCartItems(filteredItems);
            return filteredItems;
        }

        function closeUnavailableModal() {
            if (!unavailableModal) {
                return;
            }

            unavailableModal.hidden = true;

            if (unavailableModalConfirm) {
                unavailableModalConfirm.textContent = unavailableModalConfirmDefaultText;
            }
        }

        function openUnavailableModal(items, onConfirm, options) {
            if (!unavailableModal || !unavailableModalConfirm) {
                if (typeof onConfirm === "function") {
                    onConfirm();
                }
                return;
            }

            var unavailableItems = Array.isArray(items) ? items : [];
            var unavailableNames = unavailableItems.map(function (item) {
                return String(item && item.name ? item.name : "Item");
            });
            var modalOptions = options && typeof options === "object" ? options : {};
            var customMessage = String(modalOptions.message || "").trim();
            var confirmText = String(modalOptions.confirmText || "").trim();

            if (unavailableModalMessage) {
                if (customMessage) {
                    unavailableModalMessage.textContent = customMessage;
                } else if (!unavailableNames.length) {
                    unavailableModalMessage.textContent = "Some items are no longer available and will be removed from your cart.";
                } else {
                    unavailableModalMessage.textContent = "The following items are out of stock and will be removed: " + unavailableNames.join(", ") + ".";
                }
            }

            unavailableModalConfirm.textContent = confirmText || unavailableModalConfirmDefaultText;

            unavailableModalConfirm.onclick = function () {
                closeUnavailableModal();

                if (typeof onConfirm === "function") {
                    onConfirm();
                }
            };

            unavailableModal.hidden = false;
        }

        function syncConfirmButtonAvailability() {
            if (!confirmButton) {
                return;
            }

            confirmButton.disabled = isSubmittingPendingOrder || !hasAcceptedTerms;
        }

        function updateTermsAgreeAvailability() {
            if (!termsModalScroll || !termsModalAgreeButton) {
                return;
            }

            var maxScrollTop = termsModalScroll.scrollHeight - termsModalScroll.clientHeight;

            if (maxScrollTop <= 0) {
                termsModalScrolledToEnd = true;
            } else {
                termsModalScrolledToEnd = termsModalScroll.scrollTop + termsModalScroll.clientHeight >= termsModalScroll.scrollHeight - 8;
            }

            termsModalAgreeButton.disabled = !termsModalScrolledToEnd;
        }

        function setTermsAccepted(nextAccepted, shouldPersist) {
            hasAcceptedTerms = Boolean(nextAccepted);

            if (termsCheckbox) {
                termsCheckbox.checked = hasAcceptedTerms;
            }

            if (termsConsentWrap) {
                termsConsentWrap.classList.toggle("is-accepted", hasAcceptedTerms);
            }

            syncConfirmButtonAvailability();

            if (shouldPersist !== false) {
                saveBookingSnapshot();
            }
        }

        function closeTermsModal() {
            if (!termsModal) {
                return;
            }

            termsModal.hidden = true;
        }

        function openTermsModal() {
            if (!termsModal) {
                return;
            }

            if (termsModalScroll) {
                termsModalScroll.scrollTop = 0;
            }

            termsModalScrolledToEnd = false;
            updateTermsAgreeAvailability();
            termsModal.hidden = false;
        }

        function normalizeCustomerPhone(value) {
            var digits = String(value || "").replace(/[^0-9]+/g, "");

            if (digits.length === 12 && digits.indexOf("63") === 0) {
                digits = "0" + digits.slice(2);
            }

            if (digits.length === 10 && digits.indexOf("9") === 0) {
                digits = "0" + digits;
            }

            if (digits.length !== 11 || digits.indexOf("09") !== 0) {
                return "";
            }

            return digits;
        }

        function hasCustomerContactNumber() {
            return normalizeCustomerPhone(customerPhone) !== "";
        }

        function setBookingNoteLinkVisibility(isVisible) {
            if (!bookingNoteLink) {
                return;
            }

            bookingNoteLink.hidden = !Boolean(isVisible);
        }

        function setOrderCancelError(message) {
            if (!orderCancelError) {
                return;
            }

            var text = String(message || "").trim();
            orderCancelError.textContent = text;
            orderCancelError.hidden = text === "";
        }

        function closeOrderCancelModal() {
            if (!orderCancelModal) {
                return;
            }

            orderCancelModal.hidden = true;
            activeCancelOrderId = "";
            setOrderCancelError("");

            if (orderCancelReasonInput) {
                orderCancelReasonInput.value = "";
            }

            if (orderCancelConfirmButton) {
                orderCancelConfirmButton.disabled = false;
            }
        }

        function openOrderCancelModal(orderId) {
            if (!orderCancelModal) {
                return;
            }

            activeCancelOrderId = String(orderId || "").trim();
            if (!activeCancelOrderId) {
                return;
            }

            if (orderCancelReasonInput) {
                orderCancelReasonInput.value = "";
            }

            setOrderCancelError("");
            orderCancelModal.hidden = false;

            window.requestAnimationFrame(function () {
                if (orderCancelReasonInput) {
                    orderCancelReasonInput.focus();
                }
            });
        }

        function normalizeGcashPaymentInfo(rawValue) {
            var source = rawValue && typeof rawValue === "object" ? rawValue : {};

            return {
                imageUrl: String(source.imageUrl || "").trim(),
                accountName: String(source.accountName || "").trim(),
                accountNumber: String(source.accountNumber || "").trim()
            };
        }

        function normalizeCustomerGcashInfo(rawValue) {
            var source = rawValue && typeof rawValue === "object" ? rawValue : {};

            return {
                customerId: String(source.customerId || source.customer_id || "").trim(),
                gcashName: String(source.gcashName || source.gcash_name || "").trim(),
                gcashNumber: String(source.gcashNumber || source.gcash_number || "").trim(),
                updatedAt: String(source.updatedAt || source.updated_at || "").trim()
            };
        }

        function normalizeCartCatalog() {
            var fallbackImage = (assetBase ? assetBase : "/") + "assets/images/main_logo.png";
            var catalog = {};

            Object.keys(cartCatalogSource).forEach(function (itemId) {
                var source = cartCatalogSource[itemId];
                if (!source || typeof source !== "object") {
                    return;
                }

                var normalizedId = String(source.id || itemId || "").trim();
                if (!normalizedId) {
                    return;
                }

                catalog[normalizedId] = {
                    id: normalizedId,
                    type: String(source.type || "").trim(),
                    productKey: String(source.productKey || source.product_key || "").trim().toLowerCase(),
                    servicePackageKey: String(source.servicePackageKey || source.service_package_key || "").trim().toLowerCase(),
                    name: String(source.name || "Item").trim() || "Item",
                    copy: String(source.copy || "").trim(),
                    image: resolveCartAssetUrl(String(source.image || "").trim()) || fallbackImage,
                    price: parseMoney(source.price || 0),
                    durationUnit: String(source.durationUnit || source.duration_unit || "").trim().toLowerCase(),
                    durationValue: Number.parseInt(source.durationValue || source.duration_value || 1, 10)
                };
            });

            return catalog;
        }

        var cartCatalog = normalizeCartCatalog();

        function getCatalogItemIdFromOrderItem(item) {
            if (!item || typeof item !== "object") {
                return "";
            }

            var itemId = String(item.itemId || item.item_id || item.id || "").trim();
            if (itemId) {
                return itemId;
            }

            var itemType = String(item.itemType || item.item_type || item.type || "").trim().toLowerCase();
            var productKey = String(item.productKey || item.product_key || "").trim().toLowerCase();

            if (!itemType || !productKey) {
                return "";
            }

            if (itemType === "camera") {
                return "camera-" + productKey;
            }

            if (itemType === "event-package") {
                return "event-" + productKey;
            }

            if (itemType === "service-package") {
                return "service-" + productKey;
            }

            return "";
        }

        function getCatalogItemMetaForOrderItem(item) {
            if (!item || typeof item !== "object") {
                return null;
            }

            var catalogItemId = getCatalogItemIdFromOrderItem(item);

            if (catalogItemId && cartCatalog[catalogItemId]) {
                return cartCatalog[catalogItemId];
            }

            var fallbackImage = (assetBase ? assetBase : "/") + "assets/images/main_logo.png";
            var fallbackPrice = parseMoney(item.price || 0);

            return {
                id: catalogItemId,
                type: String(item.itemType || item.item_type || item.type || "").trim().toLowerCase(),
                productKey: String(item.productKey || item.product_key || "").trim().toLowerCase(),
                servicePackageKey: String(item.servicePackageKey || item.service_package_key || "").trim().toLowerCase(),
                name: String(item.name || "Item").trim() || "Item",
                copy: String(item.copy || "").trim(),
                image: resolveCartAssetUrl(String(item.image || "").trim()) || fallbackImage,
                price: fallbackPrice,
                durationUnit: String(item.durationUnit || item.duration_unit || "").trim().toLowerCase(),
                durationValue: Number.parseInt(item.durationValue || item.duration_value || 1, 10)
            };
        }

        function calculateHistoryOrderTotal(order) {
            if (!order || !Array.isArray(order.items)) {
                return 0;
            }

            return order.items.reduce(function (total, item) {
                var meta = getCatalogItemMetaForOrderItem(item);
                var qty = Number.parseInt(item.qty, 10);
                var days = Number.parseInt(item.days, 10);
                var isService = meta && meta.type === "service-package";

                qty = Number.isFinite(qty) && qty > 0 ? qty : 1;
                days = Number.isFinite(days) && days > 0 ? days : 1;

                if (!meta) {
                    return total;
                }

                return total + (isService ? meta.price : (meta.price * qty * days));
            }, 0);
        }

        function setCustomerGcashModalFields() {
            if (gcashCustomerNameValue) {
                gcashCustomerNameValue.textContent = String(customerGcashInfo.gcashName || "").trim() || "-";
            }

            if (gcashCustomerNumberValue) {
                gcashCustomerNumberValue.textContent = String(customerGcashInfo.gcashNumber || "").trim() || "-";
            }
        }

        function resolveCartAssetUrl(pathValue) {
            var normalizedPath = String(pathValue || "").trim();

            if (!normalizedPath) {
                return "";
            }

            if (/^(?:https?:)?\/\//i.test(normalizedPath) || normalizedPath.indexOf("data:image/") === 0) {
                return normalizedPath;
            }

            if (normalizedPath.charAt(0) === "/") {
                return normalizedPath;
            }

            if (assetBase) {
                return assetBase + normalizedPath.replace(/^\/+/, "");
            }

            return "/" + normalizedPath.replace(/^\/+/, "");
        }

        function normalizeCustomerNotificationCount(value) {
            var parsed = Number.parseInt(String(value || "0"), 10);

            if (!Number.isFinite(parsed) || parsed < 0) {
                return 0;
            }

            return parsed;
        }

        function setCustomerNotificationBadgeCount(nextCount) {
            var normalizedCount = normalizeCustomerNotificationCount(nextCount);
            customerNotificationUnreadCount = normalizedCount;

            customerNotificationCountBadges.forEach(function (badge) {
                badge.textContent = String(normalizedCount);
            });
        }

        function normalizeCustomerNotificationRecord(record) {
            if (!record || typeof record !== "object") {
                return null;
            }

            var id = String(record.id || "").trim();

            if (!id) {
                return null;
            }

            return {
                id: id,
                type: String(record.type || "order-status").trim() || "order-status",
                orderId: String(record.orderId || record.order_id || "").trim().toUpperCase(),
                statusToken: String(record.statusToken || record.status_token || "").trim().toLowerCase(),
                title: String(record.title || "Notification").trim() || "Notification",
                summary: String(record.summary || "").trim(),
                targetView: String(record.targetView || record.target_view || "order-status").trim().toLowerCase() === "order-status"
                    ? "order-status"
                    : "order-status",
                isRead: String(record.isRead || record.is_read || "0") === "1" || Boolean(record.isRead || record.is_read),
                createdAt: String(record.createdAt || record.created_at || "").trim(),
                createdAtLabel: String(record.createdAtLabel || record.created_at_label || "").trim()
            };
        }

        function saveCustomerNotifications(nextNotifications) {
            if (!Array.isArray(nextNotifications)) {
                customerNotifications = [];
                return;
            }

            customerNotifications = nextNotifications
                .map(function (notification) {
                    return normalizeCustomerNotificationRecord(notification);
                })
                .filter(function (notification) {
                    return Boolean(notification);
                });
        }

        function updateCustomerNotificationEmptyState() {
            if (!customerNotificationEmpty) {
                return;
            }

            customerNotificationEmpty.hidden = customerNotifications.length > 0;
        }

        function setCustomerNotificationReadState(notificationId, shouldRead) {
            var targetId = String(notificationId || "").trim();

            if (!targetId) {
                return;
            }

            customerNotifications = customerNotifications.map(function (notification) {
                if (!notification || notification.id !== targetId) {
                    return notification;
                }

                return Object.assign({}, notification, {
                    isRead: Boolean(shouldRead)
                });
            });

            var unreadCount = customerNotifications.reduce(function (total, notification) {
                if (!notification || notification.isRead) {
                    return total;
                }

                return total + 1;
            }, 0);

            setCustomerNotificationBadgeCount(unreadCount);
            renderCustomerNotificationList();
        }

        function getCustomerNotificationTimeLabel(notification) {
            if (!notification || typeof notification !== "object") {
                return "";
            }

            var createdAtLabel = String(notification.createdAtLabel || "").trim();

            if (createdAtLabel) {
                return createdAtLabel;
            }

            var createdAt = String(notification.createdAt || "").trim();

            if (!createdAt) {
                return "";
            }

            var timestamp = Date.parse(createdAt);

            if (!Number.isFinite(timestamp)) {
                return createdAt;
            }

            return new Date(timestamp).toLocaleString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
                hour: "numeric",
                minute: "2-digit"
            });
        }

        function closeCustomerNotificationModal() {
            if (!customerNotificationModal) {
                return;
            }

            customerNotificationModal.hidden = true;
        }

        function openCustomerNotificationModal() {
            if (!customerNotificationModal) {
                return;
            }

            renderCustomerNotificationList();
            customerNotificationModal.hidden = false;
        }

        function renderCustomerNotificationList() {
            if (!customerNotificationList) {
                return;
            }

            customerNotificationList.innerHTML = "";
            updateCustomerNotificationEmptyState();

            customerNotifications.forEach(function (notification) {
                var item = document.createElement("button");
                item.type = "button";
                item.className = "cart-customer-notification-item" + (notification.isRead ? " is-read" : " is-unread");
                item.setAttribute("role", "listitem");
                item.setAttribute("data-customer-notification-item", "true");
                item.setAttribute("data-customer-notification-id", String(notification.id || ""));
                item.setAttribute("data-customer-notification-type", String(notification.type || "order-status"));
                item.setAttribute("data-customer-notification-order-id", String(notification.orderId || ""));
                item.setAttribute("data-customer-notification-target-view", String(notification.targetView || "order-status"));

                var titleValue = String(notification.title || "Notification").trim() || "Notification";
                var summaryValue = String(notification.summary || "").trim();
                var timeLabel = getCustomerNotificationTimeLabel(notification);

                var head = document.createElement("div");
                head.className = "cart-customer-notification-item-head";

                var title = document.createElement("p");
                title.className = "cart-customer-notification-title";
                title.textContent = titleValue;

                var time = document.createElement("span");
                time.className = "cart-customer-notification-time";
                time.textContent = timeLabel || "";

                head.appendChild(title);
                head.appendChild(time);
                item.appendChild(head);

                if (summaryValue) {
                    var summary = document.createElement("p");
                    summary.className = "cart-customer-notification-summary";
                    summary.textContent = summaryValue;
                    item.appendChild(summary);
                }

                customerNotificationList.appendChild(item);
            });
        }

        function markCustomerNotificationAsRead(notificationId) {
            var targetId = String(notificationId || "").trim();

            if (!targetId) {
                return Promise.resolve();
            }

            if (!customerNotificationMarkReadEndpoint) {
                setCustomerNotificationReadState(targetId, true);
                return Promise.resolve();
            }

            return window.fetch(customerNotificationMarkReadEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    notificationId: targetId
                })
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false
                    };
                }).then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            }).then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    setCustomerNotificationReadState(targetId, true);
                    return;
                }

                setCustomerNotificationReadState(targetId, true);

                if (typeof result.payload.unreadCount !== "undefined") {
                    setCustomerNotificationBadgeCount(result.payload.unreadCount);
                }
            }).catch(function () {
                setCustomerNotificationReadState(targetId, true);
            });
        }

        function markCustomerOrderNotificationsAsRead() {
            var targetType = "order-status";
            var unreadOrderNotificationIds = customerNotifications
                .filter(function (notification) {
                    if (!notification || typeof notification !== "object") {
                        return false;
                    }

                    return String(notification.type || "").trim().toLowerCase() === targetType && !notification.isRead;
                })
                .map(function (notification) {
                    return String(notification.id || "").trim();
                })
                .filter(function (notificationId) {
                    return notificationId !== "";
                });

            if (!unreadOrderNotificationIds.length) {
                return Promise.resolve();
            }

            // Apply immediate UX feedback so order badges clear as soon as one order notification is opened.
            customerNotifications = customerNotifications.map(function (notification) {
                if (!notification || typeof notification !== "object") {
                    return notification;
                }

                if (String(notification.type || "").trim().toLowerCase() !== targetType) {
                    return notification;
                }

                return Object.assign({}, notification, {
                    isRead: true
                });
            });

            var unreadCount = customerNotifications.reduce(function (total, notification) {
                if (!notification || notification.isRead) {
                    return total;
                }

                return total + 1;
            }, 0);

            setCustomerNotificationBadgeCount(unreadCount);
            renderCustomerNotificationList();

            if (!customerNotificationMarkReadEndpoint) {
                return Promise.resolve();
            }

            return window.fetch(customerNotificationMarkReadEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    markAllOrderNotifications: true
                })
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false
                    };
                }).then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            }).then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    return;
                }

                if (typeof result.payload.unreadCount !== "undefined") {
                    setCustomerNotificationBadgeCount(result.payload.unreadCount);
                }
            }).catch(function () {
                // Keep optimistic UI state; live polling will reconcile if needed.
            });
        }

        function setCartViewQueryParam(nextView) {
            if (!window.history || typeof window.history.replaceState !== "function") {
                return;
            }

            try {
                var nextUrl = new URL(window.location.href);

                if (nextView === "order-status" || nextView === "services-cart" || nextView === "history") {
                    nextUrl.searchParams.set("view", nextView);
                } else {
                    nextUrl.searchParams.delete("view");
                }

                var pathWithQuery = nextUrl.pathname + nextUrl.search + nextUrl.hash;
                window.history.replaceState(null, "", pathWithQuery);
            } catch (error) {
                // Ignore URL parsing failures and keep current route.
            }
        }

        function setExpandedOrderStatusEntry(orderId) {
            activeOrderStatusEntryId = String(orderId || "").trim();
        }

        function syncOrderStatusColumnWidths() {
            if (!orderStatusList) {
                return;
            }

            var firstSummary = orderStatusList.querySelector(".cart-order-status-summary");
            if (!firstSummary) {
                return;
            }

            var article = firstSummary.closest(".profile-order-item");
            if (!article) {
                return;
            }

            var articleWidth = article.getBoundingClientRect().width;
            if (!Number.isFinite(articleWidth) || articleWidth <= 0) {
                return;
            }

            var itemsPx = Math.floor(articleWidth * 0.44);
            var schedulePx = Math.floor(articleWidth * 0.28);
            var statusPx = Math.max(0, Math.floor(articleWidth - itemsPx - schedulePx));

            orderStatusList.style.setProperty("--order-col-items", itemsPx + "px");
            orderStatusList.style.setProperty("--order-col-schedule", schedulePx + "px");
            orderStatusList.style.setProperty("--order-col-status", statusPx + "px");
        }

        function focusOrderStatusSummaryButton(orderId) {
            var targetOrderId = String(orderId || "").trim();

            if (!targetOrderId || !orderStatusList) {
                return;
            }

            window.requestAnimationFrame(function () {
                var matchedButton = null;

                orderStatusList.querySelectorAll("[data-cart-order-summary-toggle]").forEach(function (button) {
                    if (matchedButton) {
                        return;
                    }

                    if (String(button.getAttribute("data-cart-order-id") || "").trim() !== targetOrderId) {
                        return;
                    }

                    matchedButton = button;
                });

                if (matchedButton && typeof matchedButton.focus === "function") {
                    matchedButton.focus();
                }
            });
        }

        function highlightOrderStatusEntry(orderId) {
            var targetOrderId = String(orderId || "").trim();

            if (!targetOrderId || !orderStatusList) {
                return;
            }

            var matchedNode = null;

            orderStatusList.querySelectorAll("[data-cart-order-entry-id]").forEach(function (node) {
                if (matchedNode) {
                    return;
                }

                if (String(node.getAttribute("data-cart-order-entry-id") || "").trim() !== targetOrderId) {
                    return;
                }

                matchedNode = node;
            });

            if (!matchedNode) {
                return;
            }

            matchedNode.classList.remove("is-flash-highlight");
            matchedNode.classList.add("is-flash-highlight");
            matchedNode.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            window.setTimeout(function () {
                matchedNode.classList.remove("is-flash-highlight");
            }, 1500);
        }

        function closeRefundProofModal() {
            if (!refundProofModal) {
                return;
            }

            refundProofModal.hidden = true;

            if (refundProofModalImage) {
                refundProofModalImage.hidden = true;
                refundProofModalImage.removeAttribute("src");
            }

            if (refundProofModalEmpty) {
                refundProofModalEmpty.hidden = true;
                refundProofModalEmpty.textContent = "Unable to load refund proof screenshot.";
            }
        }

        function openRefundProofModal(imageUrl) {
            if (!refundProofModal || !refundProofModalImage) {
                return;
            }

            var normalizedImageUrl = String(imageUrl || "").trim();
            if (!normalizedImageUrl) {
                return;
            }

            if (refundProofModalEmpty) {
                refundProofModalEmpty.hidden = true;
            }

            refundProofModalImage.hidden = false;
            refundProofModalImage.src = normalizedImageUrl;
            refundProofModal.hidden = false;
        }

        function normalizeCustomerDeliveryStatusToken(value, leg) {
            var normalizedLeg = String(leg || "").toLowerCase().trim() === "return"
                ? "return"
                : "receive";
            var token = String(value || "").toLowerCase().trim().replace(/[^a-z-]+/g, "");

            if (!token) {
                return normalizedLeg === "return" ? "waiting-customer-proof" : "waiting-proof";
            }

            return token;
        }

        function closeDeliveryProofModal() {
            if (!deliveryProofModal) {
                return;
            }

            deliveryProofModal.hidden = true;

            if (deliveryProofModalTitle) {
                deliveryProofModalTitle.textContent = "Delivery Receipt";
            }

            if (deliveryProofModalImage) {
                deliveryProofModalImage.hidden = true;
                deliveryProofModalImage.removeAttribute("src");
            }

            if (deliveryProofModalEmpty) {
                deliveryProofModalEmpty.hidden = true;
                deliveryProofModalEmpty.textContent = "Unable to load delivery receipt.";
            }
        }

        function openDeliveryProofModal(imageUrl, titleText) {
            if (!deliveryProofModal || !deliveryProofModalImage) {
                return;
            }

            var normalizedImageUrl = String(imageUrl || "").trim();
            if (!normalizedImageUrl) {
                return;
            }

            if (deliveryProofModalTitle) {
                deliveryProofModalTitle.textContent = String(titleText || "Delivery Receipt").trim() || "Delivery Receipt";
            }

            if (deliveryProofModalEmpty) {
                deliveryProofModalEmpty.hidden = true;
            }

            deliveryProofModalImage.hidden = false;
            deliveryProofModalImage.src = normalizedImageUrl;
            deliveryProofModal.hidden = false;
        }

        function setDeliveryUploadMessage(message, isError) {
            if (!deliveryUploadMessage) {
                return;
            }

            var text = String(message || "").trim();
            deliveryUploadMessage.textContent = text;
            deliveryUploadMessage.hidden = text === "";
            deliveryUploadMessage.classList.toggle("is-error", Boolean(isError) && text !== "");
            deliveryUploadMessage.classList.toggle("is-success", !Boolean(isError) && text !== "");
        }

        function resetDeliveryUploadSelection() {
            if (deliveryUploadFileInput) {
                deliveryUploadFileInput.value = "";
            }

            if (deliveryUploadFilename) {
                deliveryUploadFilename.textContent = "No file selected";
            }

            setDeliveryUploadMessage("", false);
        }

        function closeDeliveryUploadModal() {
            if (!deliveryUploadModal) {
                return;
            }

            deliveryUploadModal.hidden = true;
            activeDeliveryUploadOrderId = "";
            isUploadingDeliveryReceipt = false;

            if (deliveryUploadSubmitButton) {
                deliveryUploadSubmitButton.disabled = false;
            }

            if (deliveryUploadSelectButton) {
                deliveryUploadSelectButton.disabled = false;
            }

            if (deliveryUploadReferenceInput) {
                deliveryUploadReferenceInput.value = "";
            }

            if (deliveryUploadNotesInput) {
                deliveryUploadNotesInput.value = "";
            }

            if (deliveryUploadModalCopy) {
                deliveryUploadModalCopy.textContent = "Upload the courier handoff receipt to mark your return delivery as in transit.";
            }

            resetDeliveryUploadSelection();
        }

        function openDeliveryUploadModal(order) {
            if (!deliveryUploadModal) {
                return;
            }

            var orderRecord = order && typeof order === "object" ? order : null;
            if (!orderRecord) {
                return;
            }

            var orderId = String(orderRecord.id || "").trim();
            if (!orderId) {
                return;
            }

            activeDeliveryUploadOrderId = orderId;
            isUploadingDeliveryReceipt = false;

            if (deliveryUploadSubmitButton) {
                deliveryUploadSubmitButton.disabled = false;
            }

            if (deliveryUploadSelectButton) {
                deliveryUploadSelectButton.disabled = false;
            }

            if (deliveryUploadReferenceInput) {
                deliveryUploadReferenceInput.value = String(orderRecord.returnDeliveryReference || "").trim();
            }

            if (deliveryUploadNotesInput) {
                deliveryUploadNotesInput.value = String(orderRecord.returnDeliveryNotes || "").trim();
            }

            if (deliveryUploadModalCopy) {
                var uploadLabel = "Upload the courier handoff receipt to mark your return delivery as in transit.";
                var orderLabel = String(orderRecord.id || "").trim();

                if (orderLabel) {
                    uploadLabel = "Upload the courier handoff receipt for " + orderLabel + " to mark your return delivery as in transit.";
                }

                deliveryUploadModalCopy.textContent = uploadLabel;
            }

            resetDeliveryUploadSelection();
            deliveryUploadModal.hidden = false;

            window.requestAnimationFrame(function () {
                if (deliveryUploadSelectButton) {
                    deliveryUploadSelectButton.focus();
                }
            });
        }

        function setGcashModalDetails() {
            if (gcashModalName) {
                gcashModalName.textContent = gcashPaymentInfo.accountName || "Not set";
            }

            if (gcashModalNumber) {
                gcashModalNumber.textContent = gcashPaymentInfo.accountNumber || "Not set";
            }

            if (gcashModalQrImage) {
                if (gcashPaymentInfo.imageUrl) {
                    gcashModalQrImage.src = gcashPaymentInfo.imageUrl;
                    gcashModalQrImage.hidden = false;

                    if (gcashModalQrEmpty) {
                        gcashModalQrEmpty.hidden = true;
                    }
                } else {
                    gcashModalQrImage.removeAttribute("src");
                    gcashModalQrImage.hidden = true;

                    if (gcashModalQrEmpty) {
                        gcashModalQrEmpty.textContent = "GCash QR is not set yet. Please contact Rental Services.";
                        gcashModalQrEmpty.hidden = false;
                    }
                }
            }
        }

        function setGcashUploadMessage(message, isError) {
            if (!gcashUploadMessage) {
                return;
            }

            var text = String(message || "").trim();
            gcashUploadMessage.textContent = text;
            gcashUploadMessage.hidden = text === "";
            gcashUploadMessage.classList.toggle("is-error", Boolean(isError) && text !== "");
            gcashUploadMessage.classList.toggle("is-success", !Boolean(isError) && text !== "");
        }

        function resetGcashReceiptSelection() {
            if (gcashReceiptFileInput) {
                gcashReceiptFileInput.value = "";
            }

            if (gcashReceiptFilename) {
                gcashReceiptFilename.textContent = "No file selected";
            }

            setGcashUploadMessage("", false);
        }

        function setGcashModalMode(mode) {
            var normalizedMode = mode === "upload-receipt" ? "upload-receipt" : "confirm-booking";
            activeGcashModalMode = normalizedMode;

            if (gcashModalInstruction) {
                gcashModalInstruction.textContent = normalizedMode === "upload-receipt"
                    ? "Scan QR in GCash, pay, then upload your receipt."
                    : "Scan QR in GCash to continue.";
            }

            if (gcashModalContinueButton) {
                gcashModalContinueButton.hidden = normalizedMode !== "confirm-booking";
            }

            if (gcashReceiptBlock) {
                gcashReceiptBlock.hidden = normalizedMode !== "upload-receipt";
            }

            if (gcashCustomerInfoWrap) {
                gcashCustomerInfoWrap.hidden = normalizedMode !== "upload-receipt";
            }

            if (normalizedMode !== "upload-receipt") {
                if (gcashReceiptTimer) {
                    gcashReceiptTimer.hidden = true;
                    gcashReceiptTimer.textContent = "";
                }
                resetGcashReceiptSelection();
                return;
            }

            setCustomerGcashModalFields();
            updateGcashModalReceiptTimer();
        }

        function closeGcashModal() {
            if (!gcashModal) {
                return;
            }

            gcashModal.hidden = true;
            pendingGcashOrderRecord = null;
            activeGcashUploadOrderId = "";
            activeGcashModalMode = "";
            isUploadingGcashReceipt = false;

            if (gcashUploadButton) {
                gcashUploadButton.disabled = false;
            }

            if (gcashReceiptSelectButton) {
                gcashReceiptSelectButton.disabled = false;
            }

            if (gcashModalContinueButton) {
                gcashModalContinueButton.disabled = false;
            }

            if (gcashReceiptTimer) {
                gcashReceiptTimer.hidden = true;
                gcashReceiptTimer.textContent = "";
            }

            resetGcashReceiptSelection();
            startReceiptCountdownTicker();
        }

        function openGcashModal(options) {
            var modalOptions = options && typeof options === "object" ? options : {};
            var mode = modalOptions.mode === "upload-receipt" ? "upload-receipt" : "confirm-booking";
            var orderRecord = mode === "confirm-booking" ? (modalOptions.orderRecord || null) : null;
            var orderId = mode === "upload-receipt" ? String(modalOptions.orderId || "").trim() : "";

            if (!gcashModal) {
                if (mode === "confirm-booking" && orderRecord) {
                    handlePendingOrderSubmission(orderRecord);
                }
                return;
            }

            pendingGcashOrderRecord = orderRecord;
            activeGcashUploadOrderId = orderId;
            setGcashModalDetails();
            setCustomerGcashModalFields();
            resetGcashReceiptSelection();
            setGcashModalMode(mode);
            gcashModal.hidden = false;

            updateGcashModalReceiptTimer();
            startReceiptCountdownTicker();
        }

        unavailableModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeUnavailableModal();
            });
        });

        function setCartView(nextView, options) {
            var settings = options && typeof options === "object" ? options : {};
            var shouldPersist = settings.persist !== false;
            var requestedView = String(nextView || "").toLowerCase().trim();
            var normalizedView = requestedView === "order-status" || requestedView === "services-cart" || requestedView === "history"
                ? requestedView
                : "cart";
            var isOrderStatusView = normalizedView === "order-status";
            var isHistoryView = normalizedView === "history";
            var isServicesCartView = normalizedView === "services-cart";
            var isFullWidthView = isOrderStatusView || isHistoryView;

            activeCartView = normalizedView;

            if (shouldPersist) {
                setCartViewQueryParam(normalizedView);
            }

            if (cartHeaderNav) {
                cartHeaderNav.hidden = isServicesCartView;
            }

            if (cartViewPanels.cart) {
                cartViewPanels.cart.hidden = isFullWidthView;
            }

            if (cartViewPanels.orderStatus) {
                cartViewPanels.orderStatus.hidden = !isOrderStatusView;
            }

            if (cartViewPanels.history) {
                cartViewPanels.history.hidden = !isHistoryView;
            }

            if (cartSidebar) {
                cartSidebar.hidden = isFullWidthView;
            }

            if (cartLayout) {
                cartLayout.classList.toggle("is-order-status-view", isFullWidthView);
            }

            if (cartMainHeading) {
                cartMainHeading.textContent = isServicesCartView ? "SERVICE RESERVATION" : "RESERVATION";
            }

            updateDeliveryFields();
            saveBookingSnapshot();
            refreshTotals();
            renderCartItems();

            if (isOrderStatusView) {
                renderOrderStatusList();
            }

            if (isHistoryView) {
                renderHistoryList();
            }

            Array.prototype.forEach.call(cartNavButtons, function (button) {
                var buttonView = button.getAttribute("data-cart-nav") || "";
                var isActive = buttonView === normalizedView;

                button.classList.toggle("is-active", isActive);

                if (isActive) {
                    button.setAttribute("aria-current", "page");
                } else {
                    button.removeAttribute("aria-current");
                }
            });
        }

        function isServicesCartViewActive() {
            return activeCartView === "services-cart";
        }

        function getServicesCartItems() {
            var serviceItem = getServiceBookingItem();

            if (!serviceItem) {
                return [];
            }

            return [serviceItem];
        }

        function getActiveCartItems() {
            return isServicesCartViewActive() ? getServicesCartItems() : getCartItems();
        }

        function getServiceDurationConfig(item) {
            var source = item && typeof item === "object" ? item : getServiceBookingItem();

            if (!source || typeof source !== "object") {
                return {
                    unit: "hours",
                    value: 1
                };
            }

            var rawUnit = String(source.durationUnit || source.duration_unit || "").trim().toLowerCase();
            var unit = rawUnit === "days" || rawUnit === "day" ? "days" : "hours";
            var value = Number.parseInt(source.durationValue || source.duration_value || "", 10);

            if (!Number.isFinite(value) || value < 1) {
                unit = "hours";
                value = 1;
            }

            if (unit === "days") {
                value = Math.max(1, Math.min(14, value));
            } else {
                unit = "hours";
                value = Math.max(1, Math.min(24, value));
            }

            return {
                unit: unit,
                value: value
            };
        }

        function formatServiceDurationLabel(item) {
            var duration = getServiceDurationConfig(item);
            var suffix = duration.unit === "days" ? "day" : "hour";

            return String(duration.value) + " " + suffix + (duration.value === 1 ? "" : "s");
        }

        function formatHourSlotLabel(hourValue) {
            var normalizedHour = Number.parseInt(hourValue, 10);

            if (!Number.isFinite(normalizedHour) || normalizedHour < 0 || normalizedHour > 23) {
                normalizedHour = 0;
            }

            var isAfternoon = normalizedHour >= 12;
            var displayHour = normalizedHour % 12;

            if (displayHour === 0) {
                displayHour = 12;
            }

            return String(displayHour).padStart(2, "0") + ":00 " + (isAfternoon ? "PM" : "AM");
        }

        function buildHourOptions(minHour, maxHour) {
            var options = [];

            for (var hour = minHour; hour <= maxHour; hour += 1) {
                options.push({
                    value: String(hour).padStart(2, "0") + ":00",
                    label: formatHourSlotLabel(hour)
                });
            }

            return options;
        }

        function applySelectOptions(selectNode, options, selectedValue) {
            if (!selectNode) {
                return;
            }

            var sourceOptions = Array.isArray(options) ? options : [];
            var preferredValue = String(selectedValue || "").trim();

            selectNode.innerHTML = "";

            sourceOptions.forEach(function (optionConfig) {
                var optionNode = document.createElement("option");
                optionNode.value = String(optionConfig && optionConfig.value ? optionConfig.value : "").trim();
                optionNode.textContent = String(optionConfig && optionConfig.label ? optionConfig.label : optionNode.value);
                selectNode.appendChild(optionNode);
            });

            if (!selectNode.options.length) {
                return;
            }

            if (preferredValue) {
                var hasPreferredValue = Array.prototype.some.call(selectNode.options, function (optionNode) {
                    return optionNode.value === preferredValue;
                });

                if (hasPreferredValue) {
                    selectNode.value = preferredValue;
                    return;
                }
            }

            selectNode.selectedIndex = 0;
        }

        function syncServiceTimeOptionSets() {
            if (isServicesCartViewActive()) {
                applySelectOptions(receiveTimeSelect, buildHourOptions(cartBookingOpenHour, cartBookingCloseHour), receiveTimeSelect ? receiveTimeSelect.value : "");
                applySelectOptions(returnTimeSelect, buildHourOptions(0, 23), returnTimeSelect ? returnTimeSelect.value : "");
                return;
            }

            applySelectOptions(receiveTimeSelect, equipmentReceiveTimeOptions, receiveTimeSelect ? receiveTimeSelect.value : "");
            applySelectOptions(returnTimeSelect, equipmentReturnTimeOptions, returnTimeSelect ? returnTimeSelect.value : "");
        }

        function normalizeStoredOrder(order) {
            if (!order || typeof order !== "object") {
                return null;
            }

            var parsedTimeoutSeconds = Number.parseInt(
                order.paymentReceiptTimeoutSeconds || order.payment_receipt_timeout_seconds,
                10
            );
            var timeoutSeconds = Number.isFinite(parsedTimeoutSeconds) && parsedTimeoutSeconds > 0
                ? parsedTimeoutSeconds
                : paymentReceiptTimeoutSecondsDefault;

            var items = Array.isArray(order.items)
                ? order.items.map(function (item) {
                    if (!item || typeof item !== "object") {
                        return null;
                    }

                    var qty = Number.parseInt(item.qty, 10);
                    var days = Number.parseInt(item.days, 10);
                    var itemId = String(item.item_id || item.itemId || item.id || "").trim();
                    var itemType = String(item.item_type || item.itemType || item.type || "").trim();
                    var productKey = String(item.product_key || item.productKey || "").trim().toLowerCase();

                    var normalizedItem = {
                        name: String(item.name || "Item"),
                        qty: Number.isFinite(qty) && qty > 0 ? qty : 1,
                        days: Number.isFinite(days) && days > 0 ? days : 1
                    };

                    if (itemId) {
                        normalizedItem.itemId = itemId;
                    }

                    if (itemType) {
                        normalizedItem.itemType = itemType;
                    }

                    if (productKey) {
                        normalizedItem.productKey = productKey;
                    }

                    var itemCopy = String(item.copy || "").trim();
                    if (itemCopy) {
                        normalizedItem.copy = itemCopy;
                    }

                    var itemImage = String(item.image || "").trim();
                    if (itemImage) {
                        normalizedItem.image = itemImage;
                    }

                    var itemPrice = String(item.price || "").trim();
                    if (itemPrice) {
                        normalizedItem.price = itemPrice;
                    }

                    var itemDurationUnit = String(item.durationUnit || item.duration_unit || "").trim().toLowerCase();
                    if (itemDurationUnit) {
                        normalizedItem.durationUnit = itemDurationUnit;
                    }

                    var itemDurationValue = Number.parseInt(item.durationValue || item.duration_value || "", 10);
                    if (Number.isFinite(itemDurationValue) && itemDurationValue > 0) {
                        normalizedItem.durationValue = itemDurationValue;
                    }

                    return normalizedItem;
                }).filter(function (item) {
                    return Boolean(item);
                })
                : [];

            return {
                id: String(order.id || ""),
                status: String(order.status || "Pending"),
                statusToken: String(order.statusToken || order.status_token || "").toLowerCase().trim(),
                paymentStage: String(order.paymentStage || order.payment_stage || "").toLowerCase().trim(),
                paymentStageLabel: String(order.paymentStageLabel || order.payment_stage_label || ""),
                waitingForPaymentReceipt: Boolean(order.waitingForPaymentReceipt)
                    || String(order.waiting_for_payment_receipt || "") === "1",
                waitingForPaymentReview: Boolean(order.waitingForPaymentReview)
                    || String(order.waiting_for_payment_review || "") === "1",
                forPickupReady: Boolean(order.forPickupReady)
                    || String(order.for_pickup_ready || "") === "1",
                receiveHandoverConfirmed: Boolean(order.receiveHandoverConfirmed)
                    || String(order.receive_handover_confirmed || "") === "1",
                receiveHandoverConfirmedAt: String(order.receiveHandoverConfirmedAt || order.receive_handover_confirmed_at || ""),
                receiveHandoverConfirmedBy: String(order.receiveHandoverConfirmedBy || order.receive_handover_confirmed_by || ""),
                items: items,
                receiveDate: String(order.receiveDate || ""),
                receiveTime: String(order.receiveTime || ""),
                returnDate: String(order.returnDate || ""),
                returnTime: String(order.returnTime || ""),
                place: String(order.place || ""),
                receivingMethod: String(order.receivingMethod || order.receiving_method || ""),
                returningMethod: String(order.returningMethod || order.returning_method || ""),
                courier: String(order.courier || ""),
                requiresReceiveDelivery: Boolean(order.requiresReceiveDelivery)
                    || String(order.requires_receive_delivery || "") === "1",
                requiresReturnDelivery: Boolean(order.requiresReturnDelivery)
                    || String(order.requires_return_delivery || "") === "1",
                receiveDeliveryStatus: String(order.receiveDeliveryStatus || order.receive_delivery_status || ""),
                receiveDeliveryReceiptPath: String(order.receiveDeliveryReceiptPath || order.receive_delivery_receipt_path || ""),
                receiveDeliveryReceiptUploadedAt: String(order.receiveDeliveryReceiptUploadedAt || order.receive_delivery_receipt_uploaded_at || ""),
                receiveDeliveryReceiptUploadedBy: String(order.receiveDeliveryReceiptUploadedBy || order.receive_delivery_receipt_uploaded_by || ""),
                receiveDeliveryReference: String(order.receiveDeliveryReference || order.receive_delivery_reference || ""),
                receiveDeliveryNotes: String(order.receiveDeliveryNotes || order.receive_delivery_notes || ""),
                receiveDeliveryClosedAt: String(order.receiveDeliveryClosedAt || order.receive_delivery_closed_at || ""),
                receiveDeliveryClosedBy: String(order.receiveDeliveryClosedBy || order.receive_delivery_closed_by || ""),
                returnDeliveryStatus: String(order.returnDeliveryStatus || order.return_delivery_status || ""),
                returnDeliveryReceiptPath: String(order.returnDeliveryReceiptPath || order.return_delivery_receipt_path || ""),
                returnDeliveryReceiptUploadedAt: String(order.returnDeliveryReceiptUploadedAt || order.return_delivery_receipt_uploaded_at || ""),
                returnDeliveryReceiptUploadedBy: String(order.returnDeliveryReceiptUploadedBy || order.return_delivery_receipt_uploaded_by || ""),
                returnDeliveryReference: String(order.returnDeliveryReference || order.return_delivery_reference || ""),
                returnDeliveryNotes: String(order.returnDeliveryNotes || order.return_delivery_notes || ""),
                returnDeliveryClosedAt: String(order.returnDeliveryClosedAt || order.return_delivery_closed_at || ""),
                returnDeliveryClosedBy: String(order.returnDeliveryClosedBy || order.return_delivery_closed_by || ""),
                validIdPath: String(order.validIdPath || order.valid_id_path || ""),
                validIdUploadedAt: String(order.validIdUploadedAt || order.valid_id_uploaded_at || ""),
                selfieWithIdPath: String(order.selfieWithIdPath || order.selfie_with_id_path || ""),
                selfieWithIdUploadedAt: String(order.selfieWithIdUploadedAt || order.selfie_with_id_uploaded_at || ""),
                requiresIdentityDocuments: Boolean(order.requiresIdentityDocuments)
                    || String(order.receivingMethod || order.receiving_method || "").toLowerCase().trim() === "delivery"
                    || String(order.returningMethod || order.returning_method || "").toLowerCase().trim() === "delivery",
                cancelReason: String(order.cancelReason || order.cancel_reason || ""),
                cancelBy: String(order.cancelBy || order.cancel_by || ""),
                paymentMethod: String(order.paymentMethod || order.payment_method || ""),
                paymentReceiptPath: String(order.paymentReceiptPath || order.payment_receipt_path || ""),
                paymentReceiptUploadedAt: String(order.paymentReceiptUploadedAt || order.payment_receipt_uploaded_at || ""),
                paymentReceiptDeadlineAt: String(order.paymentReceiptDeadlineAt || order.payment_receipt_deadline_at || ""),
                forReturnGraceSeconds: Number.parseInt(order.forReturnGraceSeconds || order.for_return_grace_seconds || 3600, 10),
                forReturnPenaltyPerHour: Number.parseInt(order.forReturnPenaltyPerHour || order.for_return_penalty_per_hour || 50, 10),
                forReturnDeadlineAt: String(order.forReturnDeadlineAt || order.for_return_deadline_at || ""),
                forReturnRemainingSeconds: Number.parseInt(order.forReturnRemainingSeconds || order.for_return_remaining_seconds || 0, 10),
                forReturnOverdueSeconds: Number.parseInt(order.forReturnOverdueSeconds || order.for_return_overdue_seconds || 0, 10),
                forReturnPenaltyHours: Number.parseInt(order.forReturnPenaltyHours || order.for_return_penalty_hours || 0, 10),
                forReturnPenaltyAmount: Number.parseInt(order.forReturnPenaltyAmount || order.for_return_penalty_amount || 0, 10),
                refundProofPath: String(order.refundProofPath || order.refund_proof_path || ""),
                refundProofUploadedAt: String(order.refundProofUploadedAt || order.refund_proof_uploaded_at || ""),
                paymentReceiptTimeoutSeconds: timeoutSeconds,
                createdAt: String(order.createdAt || order.created_at || "")
            };
        }

        function getStoredOrders() {
            return serverOrders
                .map(function (order) {
                    return normalizeStoredOrder(order);
                })
                .filter(function (order) {
                    return Boolean(order);
                });
        }

        function saveStoredOrders(orders) {
            if (!Array.isArray(orders)) {
                serverOrders = [];
                renderHistoryList();
                return;
            }

            serverOrders = orders
                .map(function (order) {
                    return normalizeStoredOrder(order);
                })
                .filter(function (order) {
                    return Boolean(order);
                });

            renderHistoryList();
        }

        function applyCustomerLivePayload(payload) {
            if (!payload || typeof payload !== "object") {
                return;
            }

            if (typeof payload.unreadCount !== "undefined") {
                setCustomerNotificationBadgeCount(payload.unreadCount);
            }

            if (Array.isArray(payload.notifications)) {
                saveCustomerNotifications(payload.notifications);
                renderCustomerNotificationList();
            }

            var nextSignature = String(payload.ordersSignature || "").trim();
            var shouldRefreshOrders = false;

            if (nextSignature !== "" && nextSignature !== customerLiveLastOrdersSignature) {
                shouldRefreshOrders = true;
            }

            if (shouldRefreshOrders && Array.isArray(payload.orders)) {
                saveStoredOrders(payload.orders);
                renderOrderStatusList();

                if (activeCartView === "order-status") {
                    showCartToast("Order status updated");
                }
            }

            if (nextSignature !== "") {
                customerLiveLastOrdersSignature = nextSignature;
            }
        }

        function pollCustomerLiveUpdates() {
            if (!customerNotificationLiveEndpoint || customerLivePollInFlight || !customerNotificationTrigger) {
                return;
            }

            var requestUrl = customerNotificationLiveEndpoint
                + (customerNotificationLiveEndpoint.indexOf("?") >= 0 ? "&" : "?")
                + "t=" + encodeURIComponent(String(Date.now()))
                + "&limit=20";

            customerLivePollInFlight = true;

            window.fetch(requestUrl, {
                method: "GET",
                headers: {
                    Accept: "application/json"
                },
                credentials: "same-origin"
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        ok: false
                    };
                }).then(function (payload) {
                    return {
                        ok: response.ok,
                        payload: payload
                    };
                });
            }).then(function (result) {
                if (!result.ok || !result.payload || !result.payload.ok) {
                    return;
                }

                applyCustomerLivePayload(result.payload);
            }).catch(function () {
                // Ignore transient polling failures and retry on next interval.
            }).finally(function () {
                customerLivePollInFlight = false;
            });
        }

        function initializeCustomerLiveUpdates() {
            if (customerLivePollTimerId !== null) {
                return;
            }

            if (!customerNotificationTrigger || !customerNotificationLiveEndpoint) {
                return;
            }

            pollCustomerLiveUpdates();
            customerLivePollTimerId = window.setInterval(pollCustomerLiveUpdates, customerLivePollIntervalMs);
        }

        function findStoredOrderById(orderId) {
            var targetOrderId = String(orderId || "").trim();

            if (!targetOrderId) {
                return null;
            }

            var matchedOrder = null;

            getStoredOrders().some(function (order) {
                if (!order || typeof order !== "object") {
                    return false;
                }

                if (String(order.id || "") !== targetOrderId) {
                    return false;
                }

                matchedOrder = order;
                return true;
            });

            return matchedOrder;
        }

        function parseOrderTimestampToMs(value) {
            var rawValue = String(value || "").trim();

            if (!rawValue) {
                return Number.NaN;
            }

            var parsedValue = Date.parse(rawValue);

            if (!Number.isFinite(parsedValue)) {
                return Number.NaN;
            }

            return parsedValue;
        }

        function getOrderPaymentReceiptTimeoutSeconds(order) {
            if (!order || typeof order !== "object") {
                return paymentReceiptTimeoutSecondsDefault;
            }

            var parsedValue = Number.parseInt(order.paymentReceiptTimeoutSeconds, 10);

            if (!Number.isFinite(parsedValue) || parsedValue < 1) {
                return paymentReceiptTimeoutSecondsDefault;
            }

            return parsedValue;
        }

        function getOrderPaymentReceiptDeadlineMs(order) {
            if (!order || typeof order !== "object") {
                return Number.NaN;
            }

            var explicitDeadlineMs = parseOrderTimestampToMs(order.paymentReceiptDeadlineAt);

            if (Number.isFinite(explicitDeadlineMs)) {
                return explicitDeadlineMs;
            }

            var createdAtMs = parseOrderTimestampToMs(order.createdAt);

            if (!Number.isFinite(createdAtMs)) {
                return Number.NaN;
            }

            return createdAtMs + (getOrderPaymentReceiptTimeoutSeconds(order) * 1000);
        }

        function getOrderPaymentReceiptCountdownState(order) {
            var defaultState = {
                active: false,
                expired: false,
                remainingSeconds: 0,
                timeoutSeconds: paymentReceiptTimeoutSecondsDefault,
                deadlineMs: Number.NaN
            };

            if (!order || typeof order !== "object") {
                return defaultState;
            }

            var statusToken = String(order.statusToken || "").toLowerCase().trim();
            var statusSlug = statusToken || String(order.status || "").toLowerCase().trim();
            var paymentMethodSlug = String(order.paymentMethod || "").toLowerCase().trim();
            var hasPaymentReceipt = String(order.paymentReceiptPath || "").trim() !== "";
            var timeoutSeconds = getOrderPaymentReceiptTimeoutSeconds(order);

            defaultState.timeoutSeconds = timeoutSeconds;

            if (statusSlug !== "pending" || paymentMethodSlug !== "gcash" || hasPaymentReceipt) {
                return defaultState;
            }

            var deadlineMs = getOrderPaymentReceiptDeadlineMs(order);

            if (!Number.isFinite(deadlineMs)) {
                return defaultState;
            }

            var remainingMs = deadlineMs - Date.now();
            var remainingSeconds = Math.max(0, Math.ceil(remainingMs / 1000));

            return {
                active: true,
                expired: remainingMs <= 0,
                remainingSeconds: remainingSeconds,
                timeoutSeconds: timeoutSeconds,
                deadlineMs: deadlineMs
            };
        }

        function formatOrderReceiptCountdown(remainingSeconds) {
            var parsedValue = Number.parseInt(remainingSeconds, 10);
            var safeSeconds = Number.isFinite(parsedValue) && parsedValue > 0 ? parsedValue : 0;
            var minutes = Math.floor(safeSeconds / 60);
            var seconds = safeSeconds % 60;

            return padTwo(minutes) + ":" + padTwo(seconds);
        }

        function buildOrderReceiptCountdownLabel(remainingSeconds) {
            return "Upload time left: " + formatOrderReceiptCountdown(remainingSeconds);
        }

        function getOrderForReturnPenaltyPerHour(order) {
            if (!order || typeof order !== "object") {
                return 50;
            }

            var parsedValue = Number.parseInt(order.forReturnPenaltyPerHour, 10);

            if (!Number.isFinite(parsedValue) || parsedValue < 1) {
                return 50;
            }

            return parsedValue;
        }

        function getOrderForReturnDeadlineMs(order) {
            if (!order || typeof order !== "object") {
                return Number.NaN;
            }

            return parseOrderTimestampToMs(order.forReturnDeadlineAt);
        }

        function getOrderForReturnState(order) {
            var defaultState = {
                active: false,
                remainingSeconds: 0,
                overdueSeconds: 0,
                penaltyPerHour: 50,
                penaltyHours: 0,
                penaltyAmount: 0,
                deadlineMs: Number.NaN
            };

            if (!order || typeof order !== "object") {
                return defaultState;
            }

            var statusToken = String(order.statusToken || "").toLowerCase().trim();

            if (statusToken !== "return") {
                return defaultState;
            }

            var penaltyPerHour = getOrderForReturnPenaltyPerHour(order);
            var deadlineMs = getOrderForReturnDeadlineMs(order);

            if (!Number.isFinite(deadlineMs)) {
                return Object.assign({}, defaultState, {
                    active: true,
                    penaltyPerHour: penaltyPerHour
                });
            }

            var deltaMs = deadlineMs - Date.now();
            var remainingSeconds = Math.max(0, Math.ceil(deltaMs / 1000));
            var overdueSeconds = Math.max(0, Math.ceil((-deltaMs) / 1000));
            var penaltyHours = overdueSeconds > 0
                ? Math.ceil(overdueSeconds / 3600)
                : 0;

            return {
                active: true,
                remainingSeconds: remainingSeconds,
                overdueSeconds: overdueSeconds,
                penaltyPerHour: penaltyPerHour,
                penaltyHours: penaltyHours,
                penaltyAmount: penaltyHours * penaltyPerHour,
                deadlineMs: deadlineMs
            };
        }

        function formatOrderForReturnCountdown(remainingSeconds) {
            var parsedValue = Number.parseInt(remainingSeconds, 10);
            var safeSeconds = Number.isFinite(parsedValue) && parsedValue > 0 ? parsedValue : 0;
            var hours = Math.floor(safeSeconds / 3600);
            var minutes = Math.floor((safeSeconds % 3600) / 60);
            var seconds = safeSeconds % 60;

            return padTwo(hours) + ":" + padTwo(minutes) + ":" + padTwo(seconds);
        }

        function buildOrderForReturnCountdownLabel(remainingSeconds) {
            return "For Return grace time left: " + formatOrderForReturnCountdown(remainingSeconds);
        }

        function buildOrderForReturnPenaltyLabel(penaltyAmount, penaltyPerHour) {
            return "Overdue penalty: "
                + formatPeso(penaltyAmount)
                + " ("
                + formatPeso(penaltyPerHour)
                + "/hour)";
        }

        function formatOrderReturningMethod(value) {
            var token = String(value || "").toLowerCase().trim();

            if (token === "pickup") {
                return "Drop-off";
            }

            if (token === "meetup") {
                return "Meet-up";
            }

            if (token === "delivery") {
                return "Delivery";
            }

            return "-";
        }

        function buildOrderForReturnReminder(returningMethod) {
            return "Return in progress. Please hand back the camera via "
                + formatOrderReturningMethod(returningMethod)
                + ". Admin will mark Complete after handover.";
        }

        function isOrderReceiveHandoverConfirmed(order) {
            if (!order || typeof order !== "object") {
                return false;
            }

            return Boolean(order.receiveHandoverConfirmed)
                || String(order.receiveHandoverConfirmedAt || "").trim() !== "";
        }

        function isOrderForPickupReady(order) {
            if (!order || typeof order !== "object") {
                return false;
            }

            if (Boolean(order.forPickupReady)) {
                return true;
            }

            var statusToken = String(order.statusToken || "").toLowerCase().trim();
            var receivingMethodToken = String(order.receivingMethod || "").toLowerCase().trim();

            if (statusToken !== "approved" || receivingMethodToken !== "pickup" || isOrderReceiveHandoverConfirmed(order)) {
                return false;
            }

            var receiveScheduleMs = parseCartLocalScheduleTimestamp(order.receiveDate, order.receiveTime);

            if (!Number.isFinite(receiveScheduleMs)) {
                return false;
            }

            var nowMs = Date.now();
            var pickupReadyWindowStartMs = receiveScheduleMs - (60 * 60 * 1000);

            return nowMs >= pickupReadyWindowStartMs && nowMs < receiveScheduleMs;
        }

        function resolveOrderStatusDisplay(order) {
            var statusToken = String(order && order.statusToken ? order.statusToken : "").toLowerCase().trim();
            var fallbackStatusText = String(order && order.status ? order.status : "Pending");
            var fallbackStatusClass = statusToken || fallbackStatusText.toLowerCase().replace(/[^a-z0-9]+/g, "-");
            var paymentMethodSlug = String(order && order.paymentMethod ? order.paymentMethod : "").toLowerCase().trim();
            var hasPaymentReceipt = String(order && order.paymentReceiptPath ? order.paymentReceiptPath : "").trim() !== "";
            var waitingForPaymentReceipt = Boolean(order && order.waitingForPaymentReceipt)
                || (statusToken === "pending" && paymentMethodSlug === "gcash" && !hasPaymentReceipt);
            var waitingForPaymentReview = Boolean(order && order.waitingForPaymentReview)
                || (statusToken === "pending" && paymentMethodSlug === "gcash" && hasPaymentReceipt);
            var forPickupReady = isOrderForPickupReady(order);

            if (waitingForPaymentReceipt) {
                return {
                    text: "Waiting for Receipt Upload",
                    classToken: "pending",
                    waitingForPaymentReceipt: true,
                    waitingForPaymentReview: false,
                    forPickupReady: false
                };
            }

            if (waitingForPaymentReview) {
                return {
                    text: "Receipt Submitted - Awaiting Admin Review",
                    classToken: "review",
                    waitingForPaymentReceipt: false,
                    waitingForPaymentReview: true,
                    forPickupReady: false
                };
            }

            if (forPickupReady) {
                return {
                    text: "For Pickup",
                    classToken: "approved",
                    waitingForPaymentReceipt: false,
                    waitingForPaymentReview: false,
                    forPickupReady: true
                };
            }

            return {
                text: fallbackStatusText,
                classToken: fallbackStatusClass,
                waitingForPaymentReceipt: false,
                waitingForPaymentReview: false,
                forPickupReady: false
            };
        }

        function applyLocalPaymentReceiptTimeouts() {
            var existingOrders = getStoredOrders();
            var didChange = false;

            var nextOrders = existingOrders.map(function (order) {
                if (!order || typeof order !== "object") {
                    return order;
                }

                var countdownState = getOrderPaymentReceiptCountdownState(order);

                if (!countdownState.active || !countdownState.expired) {
                    return order;
                }

                didChange = true;

                return Object.assign({}, order, {
                    status: "Canceled",
                    cancelReason: paymentReceiptAutoCancelReason,
                    cancelBy: "system"
                });
            });

            if (didChange) {
                saveStoredOrders(nextOrders);

                if (bookingNote) {
                    bookingNote.textContent = "Reservation canceled automatically: payment receipt was not uploaded within 10 minutes.";
                }

                queueAutoCancelStateRefresh();
            }

            return didChange;
        }

        function updateGcashModalReceiptTimer() {
            if (!gcashReceiptTimer) {
                return;
            }

            if (!gcashModal || gcashModal.hidden || activeGcashModalMode !== "upload-receipt") {
                gcashReceiptTimer.hidden = true;
                gcashReceiptTimer.textContent = "";
                return;
            }

            var targetOrder = findStoredOrderById(activeGcashUploadOrderId);
            var countdownState = getOrderPaymentReceiptCountdownState(targetOrder);

            if (!countdownState.active) {
                gcashReceiptTimer.hidden = true;
                gcashReceiptTimer.textContent = "";
                return;
            }

            gcashReceiptTimer.hidden = false;
            gcashReceiptTimer.textContent = buildOrderReceiptCountdownLabel(countdownState.remainingSeconds);
        }

        function stopReceiptCountdownTicker() {
            if (receiptCountdownIntervalId === null) {
                return;
            }

            window.clearInterval(receiptCountdownIntervalId);
            receiptCountdownIntervalId = null;
        }

        function updateReceiptCountdownTicker() {
            if (applyLocalPaymentReceiptTimeouts()) {
                return;
            }

            if (orderStatusList) {
                var countdownNodes = orderStatusList.querySelectorAll("[data-cart-receipt-countdown]");
                var forReturnNodes = orderStatusList.querySelectorAll("[data-cart-for-return]");

                countdownNodes.forEach(function (node) {
                    var orderId = String(node.getAttribute("data-cart-order-id") || "").trim();
                    var order = findStoredOrderById(orderId);
                    var countdownState = getOrderPaymentReceiptCountdownState(order);

                    if (!countdownState.active) {
                        node.textContent = "Payment receipt timer is unavailable.";
                        return;
                    }

                    node.textContent = buildOrderReceiptCountdownLabel(countdownState.remainingSeconds);
                });

                forReturnNodes.forEach(function (node) {
                    var orderId = String(node.getAttribute("data-cart-order-id") || "").trim();
                    var order = findStoredOrderById(orderId);
                    var forReturnState = getOrderForReturnState(order);

                    if (!forReturnState.active) {
                        node.textContent = "For Return timer is unavailable.";
                        node.classList.remove("is-overdue");
                        return;
                    }

                    if (forReturnState.overdueSeconds > 0) {
                        node.classList.add("is-overdue");
                        node.textContent = buildOrderForReturnPenaltyLabel(
                            forReturnState.penaltyAmount,
                            forReturnState.penaltyPerHour
                        );
                        return;
                    }

                    node.classList.remove("is-overdue");
                    node.textContent = buildOrderForReturnCountdownLabel(forReturnState.remainingSeconds);
                });
            }

            updateGcashModalReceiptTimer();
        }

        function startReceiptCountdownTicker() {
            stopReceiptCountdownTicker();

            var hasOrderCountdown = Boolean(orderStatusList && orderStatusList.querySelector("[data-cart-receipt-countdown]"));
            var hasForReturnCountdown = Boolean(orderStatusList && orderStatusList.querySelector("[data-cart-for-return]"));
            var hasModalCountdown = Boolean(gcashModal && !gcashModal.hidden && activeGcashModalMode === "upload-receipt");

            if (!hasOrderCountdown && !hasForReturnCountdown && !hasModalCountdown) {
                return;
            }

            updateReceiptCountdownTicker();
            receiptCountdownIntervalId = window.setInterval(updateReceiptCountdownTicker, 1000);
        }

        function queueAutoCancelStateRefresh() {
            if (isAutoCancelReloadQueued) {
                return;
            }

            isAutoCancelReloadQueued = true;
            stopReceiptCountdownTicker();

            window.setTimeout(function () {
                window.location.reload();
            }, 250);
        }

        function submitPendingOrder(orderRecord) {
            if (!orderSubmitEndpoint) {
                return Promise.reject(new Error("Order endpoint is unavailable."));
            }

            return window.fetch(orderSubmitEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    order: orderRecord
                })
            }).then(function (response) {
                return response.text().then(function (rawBody) {
                    var payload = {};

                    try {
                        payload = JSON.parse(rawBody);
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok || !payload || payload.ok !== true) {
                        var errorMessage = payload && payload.message
                            ? String(payload.message)
                            : "Unable to save your reservation right now.";
                        throw new Error(errorMessage);
                    }

                    return payload;
                });
            });
        }

        function submitOrderCancellation(orderId, reasonText) {
            if (!orderCancelEndpoint) {
                return Promise.reject(new Error("Cancel endpoint is unavailable."));
            }

            return window.fetch(orderCancelEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    orderId: String(orderId || ""),
                    reason: String(reasonText || "")
                })
            }).then(function (response) {
                return response.text().then(function (rawBody) {
                    var payload = {};

                    try {
                        payload = JSON.parse(rawBody);
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok || !payload || payload.ok !== true) {
                        var errorMessage = payload && payload.message
                            ? String(payload.message)
                            : "Unable to cancel your reservation right now.";
                        throw new Error(errorMessage);
                    }

                    return payload;
                });
            });
        }

        function submitOrderReceiptUpload(orderId, imageDataUrl, customerGcashName, customerGcashNumber) {
            if (!orderReceiptUploadEndpoint) {
                return Promise.reject(new Error("Receipt upload endpoint is unavailable."));
            }

            return window.fetch(orderReceiptUploadEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    orderId: String(orderId || ""),
                    imageDataUrl: String(imageDataUrl || ""),
                    customerGcashName: String(customerGcashName || ""),
                    customerGcashNumber: String(customerGcashNumber || "")
                })
            }).then(function (response) {
                return response.text().then(function (rawBody) {
                    var payload = {};

                    try {
                        payload = JSON.parse(rawBody);
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok || !payload || payload.ok !== true) {
                        var errorMessage = payload && payload.message
                            ? String(payload.message)
                            : "Unable to upload payment receipt right now.";
                        var uploadError = new Error(errorMessage);
                        uploadError.payload = payload;
                        throw uploadError;
                    }

                    return payload;
                });
            });
        }

        function submitOrderDeliveryReceiptUpload(orderId, imageDataUrl, deliveryReference, deliveryNotes) {
            if (!orderDeliveryReceiptUploadEndpoint) {
                return Promise.reject(new Error("Delivery receipt upload endpoint is unavailable."));
            }

            return window.fetch(orderDeliveryReceiptUploadEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    orderId: String(orderId || ""),
                    imageDataUrl: String(imageDataUrl || ""),
                    deliveryReference: String(deliveryReference || ""),
                    deliveryNotes: String(deliveryNotes || "")
                })
            }).then(function (response) {
                return response.text().then(function (rawBody) {
                    var payload = {};

                    try {
                        payload = JSON.parse(rawBody);
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok || !payload || payload.ok !== true) {
                        var errorMessage = payload && payload.message
                            ? String(payload.message)
                            : "Unable to upload return-delivery receipt right now.";
                        throw new Error(errorMessage);
                    }

                    return payload;
                });
            });
        }

        function handlePendingOrderSubmission(pendingOrder) {
            if (!pendingOrder || isSubmittingPendingOrder) {
                return;
            }

            isSubmittingPendingOrder = true;
            syncConfirmButtonAvailability();

            if (gcashModalContinueButton) {
                gcashModalContinueButton.disabled = true;
            }

            submitPendingOrder(pendingOrder)
                .then(function (responsePayload) {
                    var savedOrder = normalizeStoredOrder(responsePayload.order || pendingOrder);
                    var existingOrders = getStoredOrders();

                    closeGcashModal();

                    existingOrders.unshift(savedOrder);
                    saveStoredOrders(existingOrders);
                    renderOrderStatusList();

                    if (isServicesCartViewActive()) {
                        clearServiceBookingItem();
                    } else {
                        saveCartItems([]);
                    }

                    setTermsAccepted(false);
                    renderCartItems();

                    if (bookingNote) {
                        bookingNote.textContent = "Reservation saved as Pending. Open Order Status to track your reservation.";
                    }

                    showCartToast("Reservation saved as Pending");
                    setCartView("order-status");
                })
                .catch(function (error) {
                    if (bookingNote) {
                        bookingNote.textContent = error && error.message
                            ? String(error.message)
                            : "Unable to save your reservation right now.";
                    }
                })
                .finally(function () {
                    isSubmittingPendingOrder = false;

                    if (gcashModalContinueButton) {
                        gcashModalContinueButton.disabled = false;
                    }

                    syncConfirmButtonAvailability();
                });
        }

        function formatTimeDisplay(timeValue) {
            var normalized = String(timeValue || "").trim();
            if (!normalized) {
                return "";
            }

            var parts = normalized.split(":");
            if (parts.length < 2) {
                return normalized;
            }

            var hour = Number.parseInt(parts[0], 10);
            var minute = Number.parseInt(parts[1], 10);

            if (!Number.isFinite(hour) || !Number.isFinite(minute)) {
                return normalized;
            }

            var suffix = hour >= 12 ? "PM" : "AM";
            var hour12 = hour % 12;
            if (hour12 === 0) {
                hour12 = 12;
            }

            return padTwo(hour12) + ":" + padTwo(minute) + " " + suffix;
        }

        function formatDateDisplay(dateValue) {
            var normalized = String(dateValue || "").trim();
            if (!normalized) {
                return "";
            }

            var parts = normalized.split("-");
            if (parts.length !== 3) {
                return normalized;
            }

            var year = Number.parseInt(parts[0], 10);
            var month = Number.parseInt(parts[1], 10);
            var day = Number.parseInt(parts[2], 10);

            if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
                return normalized;
            }

            var parsedDate = new Date(year, month - 1, day);
            if (Number.isNaN(parsedDate.getTime())) {
                return normalized;
            }

            return parsedDate.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric"
            });
        }

        function formatOrderSchedule(dateValue, timeValue) {
            var dateLabel = formatDateDisplay(dateValue);
            var timeLabel = formatTimeDisplay(timeValue);

            if (dateLabel && timeLabel) {
                return dateLabel + " " + timeLabel;
            }

            return dateLabel || timeLabel;
        }

        function buildOrderItemsSummary(items) {
            if (!Array.isArray(items) || !items.length) {
                return "No items";
            }

            return items.map(function (item) {
                var name = String(item.name || "Item");
                var qty = Number.isFinite(item.qty) && item.qty > 0 ? item.qty : 1;
                var days = Number.isFinite(item.days) && item.days > 0 ? item.days : 1;
                var dayLabel = days === 1 ? "day" : "days";

                return name + " x" + qty + " (" + days + " " + dayLabel + ")";
            }).join(", ");
        }

        function getBookingUploadInput(fieldName) {
            if (!bookingCard) {
                return null;
            }

            var targetField = String(fieldName || "").trim();
            if (!targetField) {
                return null;
            }

            return bookingCard.querySelector("input[type='file'][data-booking-field='" + targetField + "']");
        }

        function readBookingImageFileAsDataUrl(file) {
            return new Promise(function (resolve, reject) {
                if (!(file instanceof File)) {
                    reject(new Error("Please upload both required identity images."));
                    return;
                }

                var reader = new FileReader();

                reader.onerror = function () {
                    reject(new Error("Unable to read one of the identity images."));
                };

                reader.onload = function (loadEvent) {
                    var imageDataUrl = String(loadEvent && loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");

                    if (imageDataUrl.indexOf("data:image/") !== 0) {
                        reject(new Error("Identity verification uploads must be image files."));
                        return;
                    }

                    resolve(imageDataUrl);
                };

                reader.readAsDataURL(file);
            });
        }

        function createPendingOrderRecord(items) {
            var booking = getBookingSnapshot();
            var source = Array.isArray(items) ? items : getActiveCartItems();

            if (!hasValidCurrentReceiveSchedule(source)) {
                return Promise.reject(new Error("No available receiving slots for the selected quantities and rental days. Adjust your cart or choose a later schedule."));
            }

            var normalizedItems = source
                .filter(function (item) {
                    return item && !isUnavailableCartItem(item);
                })
                .map(function (item) {
                    var normalizedItem = {
                        name: String(item.name || "Item"),
                        qty: Number.isFinite(item.qty) && item.qty > 0 ? item.qty : 1,
                        days: Number.isFinite(item.days) && item.days > 0 ? item.days : 1
                    };

                    var itemId = String(item.id || item.itemId || item.item_id || "").trim();
                    var itemType = String(item.type || item.itemType || item.item_type || "").trim();
                    var productKey = extractCameraProductKeyFromCartItem(item);
                    var servicePackageKey = String(item.servicePackageKey || item.service_package_key || "").trim().toLowerCase();
                    var itemDurationMinHours = Number.parseInt(item.durationMinHours || item.duration_min_hours || 1, 10);
                    var itemDurationMaxHours = Number.parseInt(item.durationMaxHours || item.duration_max_hours || itemDurationMinHours, 10);

                    if (itemId) {
                        normalizedItem.itemId = itemId;
                    }

                    if (itemType) {
                        normalizedItem.itemType = itemType;
                    }

                    if (productKey) {
                        normalizedItem.productKey = productKey;
                    }

                    if (String(item.copy || "").trim()) {
                        normalizedItem.copy = String(item.copy || "").trim();
                    }

                    if (String(item.image || "").trim()) {
                        normalizedItem.image = String(item.image || "").trim();
                    }

                    normalizedItem.price = String(item.price || "0");

                    if (servicePackageKey) {
                        if (!Number.isFinite(itemDurationMinHours) || itemDurationMinHours < 1) {
                            itemDurationMinHours = 1;
                        }

                        if (!Number.isFinite(itemDurationMaxHours) || itemDurationMaxHours < itemDurationMinHours) {
                            itemDurationMaxHours = itemDurationMinHours;
                        }

                        normalizedItem.servicePackageKey = servicePackageKey;
                        normalizedItem.durationMinHours = itemDurationMinHours;
                        normalizedItem.durationMaxHours = itemDurationMaxHours;
                        normalizedItem.durationUnit = String(item.durationUnit || "").trim().toLowerCase() || "hours";
                        normalizedItem.durationValue = Number.parseInt(item.durationValue || 1, 10) || 1;
                    }

                    return normalizedItem;
                });

            if (!normalizedItems.length) {
                return Promise.reject(new Error("Unable to create your reservation. Please check your selected items and try again."));
            }

            if (!booking.receiveDate || !booking.receiveTime || !booking.returnDate || !booking.returnTime) {
                return Promise.reject(new Error("Please complete your reservation schedule before confirming."));
            }

            var paymentMethodToken = String(booking.paymentMethod || "").toLowerCase().trim();

            if (!paymentMethodToken) {
                return Promise.reject(new Error("Please select a valid payment method."));
            }

            if (booking.receivingMethod === "delivery" && paymentMethodToken !== "gcash") {
                return Promise.reject(new Error("Receiving via delivery requires GCash payment."));
            }

            var pendingOrder = {
                id: "booking-" + Date.now() + "-" + Math.floor(Math.random() * 900 + 100),
                status: "Pending",
                items: normalizedItems,
                receiveDate: booking.receiveDate || "",
                receiveTime: booking.receiveTime || "",
                returnDate: booking.returnDate || "",
                returnTime: booking.returnTime || "",
                place: booking.place || "",
                receivingMethod: booking.receivingMethod || "",
                returningMethod: booking.returningMethod || "",
                courier: booking.courier || "",
                paymentMethod: booking.paymentMethod || "",
                createdAt: new Date().toISOString()
            };

            var requiresIdentityDocuments = booking.receivingMethod === "delivery"
                || booking.returningMethod === "delivery";

            if (!requiresIdentityDocuments) {
                return Promise.resolve(pendingOrder);
            }

            var validIdInput = getBookingUploadInput("validIdImage");
            var selfieWithIdInput = getBookingUploadInput("selfieWithId");
            var validIdFile = validIdInput && validIdInput.files && validIdInput.files.length
                ? validIdInput.files[0]
                : null;
            var selfieWithIdFile = selfieWithIdInput && selfieWithIdInput.files && selfieWithIdInput.files.length
                ? selfieWithIdInput.files[0]
                : null;

            if (!validIdFile || !selfieWithIdFile) {
                return Promise.reject(new Error("Delivery reservations require a valid ID and a selfie holding the valid ID."));
            }

            return Promise.all([
                readBookingImageFileAsDataUrl(validIdFile),
                readBookingImageFileAsDataUrl(selfieWithIdFile)
            ]).then(function (identityUploads) {
                pendingOrder.validIdImageDataUrl = identityUploads[0];
                pendingOrder.selfieWithIdImageDataUrl = identityUploads[1];

                return pendingOrder;
            });
        }

        function renderOrderStatusList() {
            if (!orderStatusList) {
                return;
            }

            if (applyLocalPaymentReceiptTimeouts()) {
                return;
            }
            orderStatusList.innerHTML = "";

            var allOrders = getStoredOrders();
            var hasExpandedOrder = false;

            if (activeOrderStatusEntryId) {
                hasExpandedOrder = allOrders.some(function (order) {
                    return String(order && order.id ? order.id : "").trim() === activeOrderStatusEntryId;
                });
            }

            if (!hasExpandedOrder && orderStatusFocusId) {
                setExpandedOrderStatusEntry(orderStatusFocusId);
                hasExpandedOrder = allOrders.some(function (order) {
                    return String(order && order.id ? order.id : "").trim() === activeOrderStatusEntryId;
                });
            }

            if (!hasExpandedOrder) {
                setExpandedOrderStatusEntry("");
            }

            if (orderStatusEmpty) {
                orderStatusEmpty.hidden = allOrders.length > 0;
            }

            allOrders.forEach(function (order, orderIndex) {
                function appendTextDetail(container, className, text) {
                    if (!container || !text) {
                        return;
                    }

                    var line = document.createElement("p");
                    line.className = className;
                    line.textContent = text;
                    container.appendChild(line);
                }

                function appendNodeDetail(container, className, node) {
                    if (!container || !node) {
                        return;
                    }

                    var line = document.createElement("p");
                    line.className = className;
                    line.appendChild(node);
                    container.appendChild(line);
                }

                var statusToken = String(order.statusToken || "").toLowerCase().trim();
                var statusDisplay = resolveOrderStatusDisplay(order);
                var statusText = statusDisplay.text;
                var statusClassToken = statusDisplay.classToken;
                var paymentMethodSlug = String(order.paymentMethod || "").toLowerCase().trim();
                var hasPaymentReceipt = String(order.paymentReceiptPath || "").trim() !== "";
                var refundProofPath = String(order.refundProofPath || "").trim();
                var refundProofUrl = resolveCartAssetUrl(refundProofPath);
                var hasRefundProof = refundProofUrl !== "";
                var cancelReason = String(order.cancelReason || "").trim();
                var requiresReceiveDelivery = Boolean(order.requiresReceiveDelivery)
                    || String(order.receivingMethod || "").toLowerCase().trim() === "delivery";
                var requiresReturnDelivery = Boolean(order.requiresReturnDelivery)
                    || String(order.returningMethod || "").toLowerCase().trim() === "delivery";
                var receiveDeliveryStatus = normalizeCustomerDeliveryStatusToken(order.receiveDeliveryStatus, "receive");
                var returnDeliveryStatus = normalizeCustomerDeliveryStatusToken(order.returnDeliveryStatus, "return");
                var receiveDeliveryReceiptUrl = resolveCartAssetUrl(order.receiveDeliveryReceiptPath);
                var returnDeliveryReceiptUrl = resolveCartAssetUrl(order.returnDeliveryReceiptPath);
                var hasReceiveDeliveryReceipt = receiveDeliveryReceiptUrl !== "";
                var hasReturnDeliveryReceipt = returnDeliveryReceiptUrl !== "";
                var receiveDeliveryReference = String(order.receiveDeliveryReference || "").trim();
                var returnDeliveryReference = String(order.returnDeliveryReference || "").trim();
                var countdownState = getOrderPaymentReceiptCountdownState(order);
                var forReturnState = getOrderForReturnState(order);
                var itemsSummaryText = buildOrderItemsSummary(order.items);
                var receiveSchedule = formatOrderSchedule(order.receiveDate, order.receiveTime) || "Not set";
                var returnSchedule = formatOrderSchedule(order.returnDate, order.returnTime) || "Not set";
                var orderReferenceText = String(order.id || "").trim() || "Pending reference";
                var orderEntryId = String(order.id || "").trim();
                var detailsPanelId = "cart-order-status-panel-" + (orderEntryId || String(orderIndex));
                var isExpanded = orderEntryId !== "" && orderEntryId === activeOrderStatusEntryId;

                var orderItem = document.createElement("article");
                orderItem.className = "profile-order-item";
                orderItem.setAttribute("data-cart-order-entry-id", orderEntryId);
                orderItem.classList.toggle("is-expanded", isExpanded);

                var summaryButton = document.createElement("button");
                summaryButton.type = "button";
                summaryButton.className = "cart-order-status-summary";
                summaryButton.setAttribute("data-cart-order-summary-toggle", "true");
                summaryButton.setAttribute("data-cart-order-id", orderEntryId);
                summaryButton.setAttribute("aria-expanded", isExpanded ? "true" : "false");
                summaryButton.setAttribute("aria-controls", detailsPanelId);

                var summaryMain = document.createElement("div");
                summaryMain.className = "cart-order-status-summary-main";

                var summaryItemsLabel = document.createElement("p");
                summaryItemsLabel.className = "cart-order-status-summary-label";
                summaryItemsLabel.textContent = "Items";
                summaryMain.appendChild(summaryItemsLabel);

                var orderedLine = document.createElement("p");
                orderedLine.className = "profile-order-id cart-order-status-ordered";
                orderedLine.textContent = itemsSummaryText;
                summaryMain.appendChild(orderedLine);

                summaryButton.appendChild(summaryMain);

                var summarySchedule = document.createElement("div");
                summarySchedule.className = "cart-order-status-summary-schedule";

                var summaryScheduleLabel = document.createElement("p");
                summaryScheduleLabel.className = "cart-order-status-summary-label";
                summaryScheduleLabel.textContent = "Schedule";
                summarySchedule.appendChild(summaryScheduleLabel);

                var summaryReceiveLine = document.createElement("p");
                summaryReceiveLine.className = "cart-order-status-date";
                summaryReceiveLine.textContent = "Receive: " + receiveSchedule;
                summarySchedule.appendChild(summaryReceiveLine);

                var summaryReturnLine = document.createElement("p");
                summaryReturnLine.className = "cart-order-status-date";
                summaryReturnLine.textContent = "Return: " + returnSchedule;
                summarySchedule.appendChild(summaryReturnLine);

                summaryButton.appendChild(summarySchedule);

                var summaryStatus = document.createElement("div");
                summaryStatus.className = "cart-order-status-summary-status";

                var summaryStatusLabel = document.createElement("p");
                summaryStatusLabel.className = "cart-order-status-summary-label";
                summaryStatusLabel.textContent = "Current Status";
                summaryStatus.appendChild(summaryStatusLabel);

                var summaryStatusBadge = document.createElement("span");
                summaryStatusBadge.className = "profile-order-status status-" + statusClassToken;
                summaryStatusBadge.textContent = statusText;
                summaryStatusBadge.title = statusText;
                summaryStatus.appendChild(summaryStatusBadge);

                var summaryEnd = document.createElement("div");
                summaryEnd.className = "cart-order-status-summary-end";
                summaryEnd.appendChild(summaryStatus);

                var summaryToggle = document.createElement("span");
                summaryToggle.className = "cart-order-status-summary-toggle";
                summaryToggle.setAttribute("aria-hidden", "true");
                summaryToggle.innerHTML = "&#9662;";
                summaryEnd.appendChild(summaryToggle);
                summaryButton.appendChild(summaryEnd);

                orderItem.appendChild(summaryButton);

                var detailPanel = document.createElement("div");
                detailPanel.className = "cart-order-status-detail-panel";
                detailPanel.id = detailsPanelId;
                detailPanel.hidden = !isExpanded;

                var detailMeta = document.createElement("div");
                detailMeta.className = "cart-order-status-detail-meta";

                var detailReference = document.createElement("p");
                detailReference.className = "cart-order-status-reference";
                detailReference.textContent = "Order ID: " + orderReferenceText;
                detailMeta.appendChild(detailReference);
                detailPanel.appendChild(detailMeta);

                var detailTable = document.createElement("div");
                detailTable.className = "cart-order-status-detail-table";

                ["Schedule", "Status", "Details", "Actions"].forEach(function (headingText) {
                    var heading = document.createElement("p");
                    heading.className = "cart-order-status-table-heading";
                    heading.textContent = headingText;
                    detailTable.appendChild(heading);
                });

                var scheduleCell = document.createElement("div");
                scheduleCell.className = "cart-order-status-cell cart-order-status-cell-schedule";
                scheduleCell.setAttribute("data-order-column-label", "Schedule");

                appendTextDetail(scheduleCell, "cart-order-status-date", "Receive: " + receiveSchedule);
                appendTextDetail(scheduleCell, "cart-order-status-date", "Return: " + returnSchedule);

                var statusCell = document.createElement("div");
                statusCell.className = "cart-order-status-cell cart-order-status-cell-status";
                statusCell.setAttribute("data-order-column-label", "Status");

                var statusBadge = document.createElement("span");
                statusBadge.className = "profile-order-status status-" + statusClassToken;
                statusBadge.textContent = statusText;
                statusBadge.title = statusText;

                statusCell.appendChild(statusBadge);

                var detailsCell = document.createElement("div");
                detailsCell.className = "cart-order-status-cell cart-order-status-cell-details";
                detailsCell.setAttribute("data-order-column-label", "Details");
                var detailsCount = 0;

                if (countdownState.active) {
                    var countdownLine = document.createElement("p");
                    countdownLine.className = "cart-order-status-countdown";
                    countdownLine.setAttribute("data-cart-receipt-countdown", "true");
                    countdownLine.setAttribute("data-cart-order-id", String(order.id || ""));
                    countdownLine.textContent = buildOrderReceiptCountdownLabel(countdownState.remainingSeconds);
                    detailsCell.appendChild(countdownLine);
                    detailsCount += 1;
                }

                if (forReturnState.active) {
                    appendTextDetail(
                        detailsCell,
                        "cart-order-status-for-return-note",
                        buildOrderForReturnReminder(order.returningMethod)
                    );
                    detailsCount += 1;

                    var forReturnLine = document.createElement("p");
                    forReturnLine.className = "cart-order-status-for-return";
                    forReturnLine.setAttribute("data-cart-for-return", "true");
                    forReturnLine.setAttribute("data-cart-order-id", String(order.id || ""));

                    if (forReturnState.overdueSeconds > 0) {
                        forReturnLine.classList.add("is-overdue");
                        forReturnLine.textContent = buildOrderForReturnPenaltyLabel(
                            forReturnState.penaltyAmount,
                            forReturnState.penaltyPerHour
                        );
                    } else {
                        forReturnLine.textContent = buildOrderForReturnCountdownLabel(forReturnState.remainingSeconds);
                    }

                    detailsCell.appendChild(forReturnLine);
                    detailsCount += 1;
                }

                if (requiresReceiveDelivery && receiveDeliveryStatus === "in-transit" && hasReceiveDeliveryReceipt) {
                    appendTextDetail(detailsCell, "cart-order-status-delivery", "Receive delivery: In Transit.");
                    detailsCount += 1;

                    if (receiveDeliveryReference) {
                        appendTextDetail(
                            detailsCell,
                            "cart-order-status-delivery-meta",
                            "Receive delivery reference: " + receiveDeliveryReference
                        );
                        detailsCount += 1;
                    }

                    if (hasReceiveDeliveryReceipt) {
                        var receiveDeliveryProofButton = document.createElement("button");
                        receiveDeliveryProofButton.type = "button";
                        receiveDeliveryProofButton.className = "cart-order-status-refund-proof-link cart-order-status-delivery-proof-open";
                        receiveDeliveryProofButton.textContent = "View receive delivery receipt";
                        receiveDeliveryProofButton.setAttribute("data-cart-order-delivery-proof-open", "true");
                        receiveDeliveryProofButton.setAttribute("data-cart-delivery-proof-url", receiveDeliveryReceiptUrl);
                        receiveDeliveryProofButton.setAttribute("data-cart-delivery-proof-title", "Receive Delivery Receipt");
                        appendNodeDetail(detailsCell, "cart-order-status-delivery-proof", receiveDeliveryProofButton);
                        detailsCount += 1;
                    }
                }

                if (requiresReturnDelivery && returnDeliveryStatus === "in-transit" && hasReturnDeliveryReceipt) {
                    appendTextDetail(detailsCell, "cart-order-status-delivery", "Return delivery: In Transit.");
                    detailsCount += 1;

                    if (returnDeliveryReference) {
                        appendTextDetail(
                            detailsCell,
                            "cart-order-status-delivery-meta",
                            "Return delivery reference: " + returnDeliveryReference
                        );
                        detailsCount += 1;
                    }

                    if (hasReturnDeliveryReceipt) {
                        var returnDeliveryProofButton = document.createElement("button");
                        returnDeliveryProofButton.type = "button";
                        returnDeliveryProofButton.className = "cart-order-status-refund-proof-link cart-order-status-delivery-proof-open";
                        returnDeliveryProofButton.textContent = "View return delivery receipt";
                        returnDeliveryProofButton.setAttribute("data-cart-order-delivery-proof-open", "true");
                        returnDeliveryProofButton.setAttribute("data-cart-delivery-proof-url", returnDeliveryReceiptUrl);
                        returnDeliveryProofButton.setAttribute("data-cart-delivery-proof-title", "Return Delivery Receipt");
                        appendNodeDetail(detailsCell, "cart-order-status-delivery-proof", returnDeliveryProofButton);
                        detailsCount += 1;
                    }
                }

                if (statusToken === "completed") {
                    appendTextDetail(
                        detailsCell,
                        "cart-order-status-refund-proof",
                        "Reservation completed. Admin has confirmed your return handover."
                    );
                    detailsCount += 1;
                }

                var shouldShowReason = (statusToken === "canceled" || statusToken === "rejected" || statusToken === "refunded") && cancelReason;
                var isAwaitingRefund = statusToken === "awaiting-refund";

                shouldShowReason = shouldShowReason || (isAwaitingRefund && cancelReason);

                if (shouldShowReason) {
                    var defaultReasonPrefix = "Reason: ";

                    if (statusToken === "rejected") {
                        defaultReasonPrefix = "Rejection reason: ";
                    } else if (statusToken === "refunded") {
                        defaultReasonPrefix = "Refund reason: ";
                    } else if (isAwaitingRefund) {
                        defaultReasonPrefix = "Cancellation reason: ";
                    }

                    appendTextDetail(
                        detailsCell,
                        "cart-order-status-reason",
                        /^(reason|cancellation reason|rejection reason|refund reason)\s*:/i.test(cancelReason)
                        ? cancelReason
                        : defaultReasonPrefix + cancelReason
                    );
                    detailsCount += 1;
                }

                if (isAwaitingRefund) {
                    appendTextDetail(
                        detailsCell,
                        "cart-order-status-refund-proof",
                        "Refund is being processed. The refund proof screenshot will be available once completed."
                    );
                    detailsCount += 1;
                }

                if (statusToken === "refunded") {
                    if (hasRefundProof) {
                        var refundProofButton = document.createElement("button");
                        refundProofButton.type = "button";
                        refundProofButton.className = "cart-order-status-refund-proof-link cart-order-status-refund-proof-open";
                        refundProofButton.textContent = "View refund proof screenshot";
                        refundProofButton.setAttribute("data-cart-order-refund-proof-open", "true");
                        refundProofButton.setAttribute("data-cart-refund-proof-url", refundProofUrl);
                        appendNodeDetail(detailsCell, "cart-order-status-refund-proof", refundProofButton);
                    } else {
                        appendTextDetail(
                            detailsCell,
                            "cart-order-status-refund-proof",
                            "Refund proof screenshot is not available yet."
                        );
                    }
                    detailsCount += 1;
                }

                if (detailsCount === 0) {
                    var detailsEmptyLine = document.createElement("p");
                    detailsEmptyLine.className = "cart-order-status-empty-detail";
                    detailsEmptyLine.textContent = "No additional updates yet.";
                    detailsCell.appendChild(detailsEmptyLine);
                }

                var actionWrap = document.createElement("div");
                actionWrap.className = "cart-order-status-cell cart-order-status-actions";
                actionWrap.setAttribute("data-order-column-label", "Actions");

                if (statusToken === "pending" && paymentMethodSlug === "gcash" && !countdownState.expired) {
                    var pendingAction = document.createElement("button");
                    pendingAction.type = "button";
                    pendingAction.className = "profile-order-action primary";
                    pendingAction.textContent = hasPaymentReceipt
                        ? "Update Payment Receipt"
                        : "Upload Payment Receipt";
                    pendingAction.setAttribute("data-cart-order-upload", "true");
                    pendingAction.setAttribute("data-cart-order-id", String(order.id || ""));
                    actionWrap.classList.add("is-stacked");
                    actionWrap.appendChild(pendingAction);
                }

                var canUploadReturnDeliveryReceipt = orderDeliveryReceiptUploadEndpoint !== ""
                    && (statusToken === "return" || statusToken === "ongoing")
                    && requiresReturnDelivery
                    && (returnDeliveryStatus === "waiting-customer-proof" || returnDeliveryStatus === "in-transit");

                if (canUploadReturnDeliveryReceipt) {
                    var deliveryUploadAction = document.createElement("button");
                    deliveryUploadAction.type = "button";
                    deliveryUploadAction.className = "profile-order-action secondary";
                    deliveryUploadAction.textContent = hasReturnDeliveryReceipt
                        ? "Update Return Delivery Receipt"
                        : "Upload Return Delivery Receipt";
                    deliveryUploadAction.setAttribute("data-cart-order-delivery-upload", "true");
                    deliveryUploadAction.setAttribute("data-cart-order-id", String(order.id || ""));
                    actionWrap.classList.add("is-stacked");
                    actionWrap.appendChild(deliveryUploadAction);
                }

                var canCancelOrder = orderCancelEndpoint !== "" && (statusToken === "pending" || statusToken === "approved");

                if (canCancelOrder) {
                    var cancelAction = document.createElement("button");
                    cancelAction.type = "button";
                    cancelAction.className = "profile-order-action danger";
                    cancelAction.textContent = "Cancel";
                    cancelAction.setAttribute("data-cart-order-cancel-open", "true");
                    cancelAction.setAttribute("data-cart-order-id", String(order.id || ""));
                    actionWrap.appendChild(cancelAction);
                }

                if (actionWrap.children.length === 0) {
                    var actionEmptyLine = document.createElement("p");
                    actionEmptyLine.className = "cart-order-status-empty-detail";
                    actionEmptyLine.textContent = "No actions available.";
                    actionWrap.appendChild(actionEmptyLine);
                }

                detailTable.appendChild(scheduleCell);
                detailTable.appendChild(statusCell);
                detailTable.appendChild(detailsCell);
                detailTable.appendChild(actionWrap);
                detailPanel.appendChild(detailTable);
                orderItem.appendChild(detailPanel);

                orderStatusList.appendChild(orderItem);
            });

            if (orderStatusFocusId) {
                highlightOrderStatusEntry(orderStatusFocusId);
                orderStatusFocusId = "";
            }

            syncOrderStatusColumnWidths();
            startReceiptCountdownTicker();
        }

        function getHistoryEligibleOrders() {
            return getStoredOrders();
        }

        function renderHistoryList() {
            if (!historyList) {
                return;
            }

            historyList.innerHTML = "";

            var allOrders = getHistoryEligibleOrders();
            var orders = window.CreatyTransactionHistory
                ? window.CreatyTransactionHistory.selectOrders("customer", allOrders)
                : allOrders;

            if (historyEmpty) {
                historyEmpty.hidden = orders.length > 0;
                historyEmpty.textContent = allOrders.length
                    ? "No reservations match these filters. Adjust or reset the filters above."
                    : "No reservations yet. Your reservations will appear here after checkout.";
            }

            orders.forEach(function (order) {
                var statusDisplay = resolveOrderStatusDisplay(order);
                var orderCard = document.createElement("article");
                orderCard.className = "cart-history-card";
                orderCard.setAttribute("data-cart-history-order-id", String(order.id || ""));

                var gallery = document.createElement("div");
                gallery.className = "cart-history-gallery";

                var galleryItems = Array.isArray(order.items) ? order.items.slice(0, 4) : [];

                galleryItems.forEach(function (item, itemIndex) {
                    var meta = getCatalogItemMetaForOrderItem(item);
                    var thumb = document.createElement("div");
                    thumb.className = "cart-history-thumb";

                    var image = document.createElement("img");
                    image.src = meta ? meta.image : ((assetBase ? assetBase : "/") + "assets/images/main_logo.png");
                    image.alt = String(item && item.name ? item.name : "Reservation item");
                    thumb.appendChild(image);

                    if (itemIndex === 3 && Array.isArray(order.items) && order.items.length > 4) {
                        var moreBadge = document.createElement("span");
                        moreBadge.className = "cart-history-thumb-more";
                        moreBadge.textContent = "+" + String(order.items.length - 4) + " more";
                        thumb.appendChild(moreBadge);
                    }

                    gallery.appendChild(thumb);
                });

                orderCard.appendChild(gallery);

                var head = document.createElement("div");
                head.className = "cart-history-head";

                var titleWrap = document.createElement("div");

                var title = document.createElement("h3");
                title.textContent = buildOrderItemsSummary(order.items);
                titleWrap.appendChild(title);

                var reference = document.createElement("p");
                reference.className = "cart-history-reference";
                reference.textContent = "Reservation ID: " + (String(order.id || "").trim() || "Pending reference");
                titleWrap.appendChild(reference);

                head.appendChild(titleWrap);

                var statusBadge = document.createElement("span");
                statusBadge.className = "profile-order-status status-" + statusDisplay.classToken;
                statusBadge.textContent = statusDisplay.text;
                head.appendChild(statusBadge);

                orderCard.appendChild(head);

                var metaBlock = document.createElement("div");
                metaBlock.className = "cart-history-meta";

                var placedTimestamp = window.CreatyTransactionHistory
                    ? window.CreatyTransactionHistory.createdTimestamp(order)
                    : Date.parse(order.createdAt);
                var placedDate = new Date(placedTimestamp);
                if (placedTimestamp !== null && Number.isFinite(placedDate.getTime())) {
                    var placedLine = document.createElement("p");
                    placedLine.className = "cart-history-reference";
                    placedLine.textContent = "Date placed: " + placedDate.toLocaleString("en-PH", {
                        timeZone: "Asia/Manila", year: "numeric", month: "short", day: "numeric", hour: "numeric", minute: "2-digit"
                    });
                    metaBlock.appendChild(placedLine);
                }

                var scheduleLine = document.createElement("p");
                scheduleLine.className = "cart-history-schedule";
                scheduleLine.textContent = "Receive: "
                    + (formatOrderSchedule(order.receiveDate, order.receiveTime) || "Not set")
                    + " | Return: "
                    + (formatOrderSchedule(order.returnDate, order.returnTime) || "Not set");
                metaBlock.appendChild(scheduleLine);

                var totalLine = document.createElement("p");
                totalLine.className = "cart-history-price";
                totalLine.textContent = "Estimated Total: " + formatMoney(calculateHistoryOrderTotal(order));
                metaBlock.appendChild(totalLine);

                if (String(order.cancelReason || "").trim()) {
                    var reasonLine = document.createElement("p");
                    reasonLine.className = "cart-history-reference";
                    reasonLine.textContent = "Reason: " + String(order.cancelReason).trim();
                    metaBlock.appendChild(reasonLine);
                }

                orderCard.appendChild(metaBlock);

                var itemList = document.createElement("div");
                itemList.className = "cart-history-items";

                (Array.isArray(order.items) ? order.items : []).forEach(function (item) {
                    var itemMeta = getCatalogItemMetaForOrderItem(item);
                    var qty = Number.parseInt(item.qty, 10);
                    var days = Number.parseInt(item.days, 10);
                    var itemCard = document.createElement("div");
                    itemCard.className = "cart-history-item";

                    qty = Number.isFinite(qty) && qty > 0 ? qty : 1;
                    days = Number.isFinite(days) && days > 0 ? days : 1;

                    var itemMain = document.createElement("div");
                    itemMain.className = "cart-history-item-main";

                    var itemName = document.createElement("p");
                    itemName.className = "cart-history-item-name";
                    itemName.textContent = String(item && item.name ? item.name : "Item");
                    itemMain.appendChild(itemName);

                    var itemCopy = document.createElement("p");
                    itemCopy.className = "cart-history-item-copy";
                    itemCopy.textContent = String(itemMeta && itemMeta.copy ? itemMeta.copy : "Ready for re-reserve.");
                    itemMain.appendChild(itemCopy);
                    itemCard.appendChild(itemMain);

                    var itemMetaText = document.createElement("p");
                    itemMetaText.className = "cart-history-item-meta";
                    itemMetaText.textContent = "Qty " + String(qty) + " | " + String(days) + " day" + (days === 1 ? "" : "s");
                    itemCard.appendChild(itemMetaText);

                    itemList.appendChild(itemCard);
                });

                orderCard.appendChild(itemList);

                var actions = document.createElement("div");
                actions.className = "cart-history-actions";

                var canReReserve = ["completed", "canceled", "rejected", "refunded"].indexOf(String(order.statusToken || "")) !== -1;
                var actionNote = document.createElement("p");
                actionNote.className = "cart-history-action-note";
                actionNote.textContent = canReReserve
                    ? "Uses the latest catalog availability and current item pricing."
                    : "Manage this reservation and track its progress in Order Status.";
                actions.appendChild(actionNote);

                var reReserveButton = document.createElement("button");
                reReserveButton.type = "button";
                reReserveButton.className = "cart-history-rereserve";
                reReserveButton.textContent = canReReserve ? "RE-RESERVE" : "VIEW STATUS";
                reReserveButton.setAttribute(canReReserve ? "data-cart-history-rereserve" : "data-cart-history-view-status", "true");
                reReserveButton.setAttribute("data-cart-order-id", String(order.id || ""));
                actions.appendChild(reReserveButton);

                orderCard.appendChild(actions);
                historyList.appendChild(orderCard);
            });
        }

        function rebuildCartItemsFromHistoryOrder(order) {
            var equipmentItems = [];
            var serviceItem = null;
            var skippedItems = [];

            (Array.isArray(order && order.items) ? order.items : []).forEach(function (item) {
                var meta = getCatalogItemMetaForOrderItem(item);
                var itemId = getCatalogItemIdFromOrderItem(item);
                var qty = Number.parseInt(item.qty, 10);
                var days = Number.parseInt(item.days, 10);

                qty = Number.isFinite(qty) && qty > 0 ? qty : 1;
                days = Number.isFinite(days) && days > 0 ? days : 1;

                if (!meta || !itemId || !availableCartItemIdsSet[itemId]) {
                    skippedItems.push({
                        name: String(item && item.name ? item.name : "Item")
                    });
                    return;
                }

                if (meta.type === "service-package") {
                    serviceItem = {
                        id: itemId,
                        type: "service-package",
                        servicePackageKey: String(meta.servicePackageKey || meta.productKey || "").trim().toLowerCase(),
                        name: meta.name,
                        copy: meta.copy,
                        image: meta.image,
                        price: meta.price,
                        qty: 1,
                        days: 1,
                        durationUnit: meta.durationUnit || "hours",
                        durationValue: Number.isFinite(meta.durationValue) && meta.durationValue > 0 ? meta.durationValue : 1
                    };
                    return;
                }

                equipmentItems.push({
                    id: itemId,
                    type: meta.type || String(item.itemType || item.item_type || "item").trim(),
                    productKey: String(meta.productKey || "").trim().toLowerCase(),
                    name: meta.name,
                    copy: meta.copy,
                    image: meta.image,
                    price: meta.price,
                    qty: qty,
                    days: days
                });
            });

            return {
                equipmentItems: equipmentItems,
                serviceItem: serviceItem,
                skippedItems: skippedItems
            };
        }

        function handleHistoryReReserve(orderId) {
            var targetOrder = findStoredOrderById(orderId);
            if (!targetOrder || ["completed", "canceled", "rejected", "refunded"].indexOf(String(targetOrder.statusToken || "")) === -1) {
                return;
            }

            var rebuilt = rebuildCartItemsFromHistoryOrder(targetOrder);

            if (!rebuilt.equipmentItems.length && !rebuilt.serviceItem) {
                showCartToast("These reservation items are currently unavailable for re-reserve.");
                return;
            }

            function applyReReserveItems() {
                if (rebuilt.equipmentItems.length) {
                    saveCartItems(rebuilt.equipmentItems);
                    clearServiceBookingItem();
                    syncCartCountBadges(rebuilt.equipmentItems);
                    setTermsAccepted(false);
                    showCartToast("Items re-added to reservation");
                    setCartView("cart");
                } else if (rebuilt.serviceItem) {
                    saveCartItems([]);
                    saveServiceBookingItem(rebuilt.serviceItem);
                    syncCartCountBadges([]);
                    setTermsAccepted(false);
                    showCartToast("Service package re-added to reservation");
                    setCartView("services-cart");
                }

                renderCartItems();
                saveBookingSnapshot();

                if (bookingNote) {
                    bookingNote.textContent = rebuilt.skippedItems.length
                        ? "Available items were re-added. Missing items were skipped."
                        : "Reservation items were re-added. Review your schedule and confirm again.";
                }
            }

            if (rebuilt.skippedItems.length) {
                openUnavailableModal(
                    rebuilt.skippedItems,
                    applyReReserveItems,
                    {
                        message: "The following items are no longer available for re-reserve and will be skipped: "
                            + rebuilt.skippedItems.map(function (item) {
                                return String(item.name || "Item");
                            }).join(", ")
                            + ".",
                        confirmText: "Skip Missing and Continue"
                    }
                );
                return;
            }

            applyReReserveItems();
        }

        if (orderStatusList) {
            orderStatusList.addEventListener("click", function (event) {
                var summaryButton = event.target.closest("[data-cart-order-summary-toggle]");
                if (summaryButton) {
                    var summaryOrderId = String(summaryButton.getAttribute("data-cart-order-id") || "").trim();
                    var shouldExpand = summaryOrderId !== "" && summaryOrderId !== activeOrderStatusEntryId;

                    setExpandedOrderStatusEntry(shouldExpand ? summaryOrderId : "");
                    renderOrderStatusList();
                    focusOrderStatusSummaryButton(summaryOrderId);
                    return;
                }

                var uploadButton = event.target.closest("[data-cart-order-upload]");
                if (uploadButton) {
                    var targetOrderId = String(uploadButton.getAttribute("data-cart-order-id") || "").trim();

                    if (!targetOrderId) {
                        return;
                    }

                    var targetOrder = findStoredOrderById(targetOrderId);
                    var countdownState = getOrderPaymentReceiptCountdownState(targetOrder);

                    if (countdownState.active && countdownState.expired) {
                        if (applyLocalPaymentReceiptTimeouts()) {
                            renderOrderStatusList();
                        }

                        return;
                    }

                    openGcashModal({
                        mode: "upload-receipt",
                        orderId: targetOrderId
                    });
                    return;
                }

                var refundProofButton = event.target.closest("[data-cart-order-refund-proof-open]");
                if (refundProofButton) {
                    var proofImageUrl = String(refundProofButton.getAttribute("data-cart-refund-proof-url") || "").trim();

                    if (!proofImageUrl) {
                        return;
                    }

                    openRefundProofModal(proofImageUrl);
                    return;
                }

                var deliveryProofButton = event.target.closest("[data-cart-order-delivery-proof-open]");
                if (deliveryProofButton) {
                    var deliveryProofImageUrl = String(deliveryProofButton.getAttribute("data-cart-delivery-proof-url") || "").trim();
                    var deliveryProofTitle = String(deliveryProofButton.getAttribute("data-cart-delivery-proof-title") || "Delivery Receipt").trim();

                    if (!deliveryProofImageUrl) {
                        return;
                    }

                    openDeliveryProofModal(deliveryProofImageUrl, deliveryProofTitle);
                    return;
                }

                var deliveryUploadButton = event.target.closest("[data-cart-order-delivery-upload]");
                if (deliveryUploadButton) {
                    var deliveryUploadOrderId = String(deliveryUploadButton.getAttribute("data-cart-order-id") || "").trim();

                    if (!deliveryUploadOrderId) {
                        return;
                    }

                    var deliveryUploadOrder = findStoredOrderById(deliveryUploadOrderId);
                    if (!deliveryUploadOrder) {
                        return;
                    }

                    openDeliveryUploadModal(deliveryUploadOrder);
                    return;
                }

                var cancelButton = event.target.closest("[data-cart-order-cancel-open]");
                if (!cancelButton) {
                    return;
                }

                var orderId = cancelButton.getAttribute("data-cart-order-id") || "";
                openOrderCancelModal(orderId);
            });
        }

        if (customerNotificationTrigger) {
            customerNotificationTrigger.addEventListener("click", function () {
                openCustomerNotificationModal();
                pollCustomerLiveUpdates();
            });
        }

        customerNotificationModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeCustomerNotificationModal();
            });
        });

        if (customerNotificationList) {
            customerNotificationList.addEventListener("click", function (event) {
                var notificationItem = event.target.closest("[data-customer-notification-item]");

                if (!notificationItem) {
                    return;
                }

                var notificationId = String(notificationItem.getAttribute("data-customer-notification-id") || "").trim();
                var notificationType = String(notificationItem.getAttribute("data-customer-notification-type") || "order-status").trim().toLowerCase();
                var targetView = String(notificationItem.getAttribute("data-customer-notification-target-view") || "order-status").trim().toLowerCase();
                var orderId = String(notificationItem.getAttribute("data-customer-notification-order-id") || "").trim();

                closeCustomerNotificationModal();

                if (targetView === "order-status") {
                    if (orderId) {
                        orderStatusFocusId = orderId;
                    }

                    setCartView("order-status");
                }

                if (notificationType === "order-status") {
                    markCustomerOrderNotificationsAsRead();
                    return;
                }

                markCustomerNotificationAsRead(notificationId);
            });
        }

        orderCancelCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeOrderCancelModal();
            });
        });

        refundProofModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeRefundProofModal();
            });
        });

        deliveryProofModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeDeliveryProofModal();
            });
        });

        deliveryUploadCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                if (isUploadingDeliveryReceipt) {
                    return;
                }

                closeDeliveryUploadModal();
            });
        });

        if (deliveryUploadSelectButton && deliveryUploadFileInput) {
            deliveryUploadSelectButton.addEventListener("click", function () {
                if (isUploadingDeliveryReceipt) {
                    return;
                }

                deliveryUploadFileInput.click();
            });
        }

        if (deliveryUploadFileInput) {
            deliveryUploadFileInput.addEventListener("change", function () {
                if (deliveryUploadFilename) {
                    if (deliveryUploadFileInput.files && deliveryUploadFileInput.files.length > 0) {
                        deliveryUploadFilename.textContent = deliveryUploadFileInput.files[0].name;
                    } else {
                        deliveryUploadFilename.textContent = "No file selected";
                    }
                }

                setDeliveryUploadMessage("", false);
            });
        }

        if (deliveryUploadSubmitButton) {
            deliveryUploadSubmitButton.addEventListener("click", function () {
                var orderId = String(activeDeliveryUploadOrderId || "").trim();
                var deliveryReference = deliveryUploadReferenceInput
                    ? String(deliveryUploadReferenceInput.value || "").trim()
                    : "";
                var deliveryNotes = deliveryUploadNotesInput
                    ? String(deliveryUploadNotesInput.value || "").trim()
                    : "";

                if (!orderId) {
                    closeDeliveryUploadModal();
                    return;
                }

                if (!deliveryUploadFileInput || !deliveryUploadFileInput.files || !deliveryUploadFileInput.files.length) {
                    setDeliveryUploadMessage("Please select a delivery receipt image first.", true);
                    return;
                }

                if (isUploadingDeliveryReceipt) {
                    return;
                }

                var selectedFile = deliveryUploadFileInput.files[0];
                var reader = new FileReader();

                isUploadingDeliveryReceipt = true;
                deliveryUploadSubmitButton.disabled = true;

                if (deliveryUploadSelectButton) {
                    deliveryUploadSelectButton.disabled = true;
                }

                setDeliveryUploadMessage("Uploading return-delivery receipt...", false);

                reader.onload = function (loadEvent) {
                    var imageDataUrl = String(loadEvent && loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");

                    if (imageDataUrl.indexOf("data:image/") !== 0) {
                        setDeliveryUploadMessage("Please upload a valid image file.", true);
                        isUploadingDeliveryReceipt = false;
                        deliveryUploadSubmitButton.disabled = false;

                        if (deliveryUploadSelectButton) {
                            deliveryUploadSelectButton.disabled = false;
                        }

                        return;
                    }

                    submitOrderDeliveryReceiptUpload(orderId, imageDataUrl, deliveryReference, deliveryNotes)
                        .then(function (responsePayload) {
                            var savedOrder = normalizeStoredOrder(responsePayload.order || null);

                            if (!savedOrder) {
                                throw new Error("Unable to refresh return-delivery status.");
                            }

                            var existingOrders = getStoredOrders();
                            var hasMatch = false;
                            var nextOrders = existingOrders.map(function (order) {
                                if (order.id === savedOrder.id) {
                                    hasMatch = true;
                                    return savedOrder;
                                }

                                return order;
                            });

                            if (!hasMatch) {
                                nextOrders.unshift(savedOrder);
                            }

                            saveStoredOrders(nextOrders);
                            renderOrderStatusList();
                            closeDeliveryUploadModal();

                            if (bookingNote) {
                                bookingNote.textContent = "Return delivery receipt uploaded. Waiting for admin closure.";
                            }

                            showCartToast("Return delivery receipt uploaded");
                            setCartView("order-status");
                        })
                        .catch(function (error) {
                            setDeliveryUploadMessage(error && error.message
                                ? String(error.message)
                                : "Unable to upload return-delivery receipt right now.", true);
                        })
                        .finally(function () {
                            isUploadingDeliveryReceipt = false;
                            deliveryUploadSubmitButton.disabled = false;

                            if (deliveryUploadSelectButton) {
                                deliveryUploadSelectButton.disabled = false;
                            }
                        });
                };

                reader.onerror = function () {
                    setDeliveryUploadMessage("Unable to read the selected image.", true);
                    isUploadingDeliveryReceipt = false;
                    deliveryUploadSubmitButton.disabled = false;

                    if (deliveryUploadSelectButton) {
                        deliveryUploadSelectButton.disabled = false;
                    }
                };

                reader.readAsDataURL(selectedFile);
            });
        }

        if (refundProofModalImage) {
            refundProofModalImage.addEventListener("error", function () {
                refundProofModalImage.hidden = true;
                refundProofModalImage.removeAttribute("src");

                if (refundProofModalEmpty) {
                    refundProofModalEmpty.textContent = "Unable to load refund proof screenshot.";
                    refundProofModalEmpty.hidden = false;
                }
            });
        }

        if (deliveryProofModalImage) {
            deliveryProofModalImage.addEventListener("error", function () {
                deliveryProofModalImage.hidden = true;
                deliveryProofModalImage.removeAttribute("src");

                if (deliveryProofModalEmpty) {
                    deliveryProofModalEmpty.textContent = "Unable to load delivery receipt.";
                    deliveryProofModalEmpty.hidden = false;
                }
            });
        }

        gcashModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeGcashModal();
            });
        });

        if (gcashReceiptSelectButton && gcashReceiptFileInput) {
            gcashReceiptSelectButton.addEventListener("click", function () {
                if (isUploadingGcashReceipt) {
                    return;
                }

                gcashReceiptFileInput.click();
            });
        }

        if (gcashReceiptFileInput) {
            gcashReceiptFileInput.addEventListener("change", function () {
                if (gcashReceiptFilename) {
                    if (gcashReceiptFileInput.files && gcashReceiptFileInput.files.length > 0) {
                        gcashReceiptFilename.textContent = gcashReceiptFileInput.files[0].name;
                    } else {
                        gcashReceiptFilename.textContent = "No file selected";
                    }
                }

                setGcashUploadMessage("", false);
            });
        }

        if (gcashModalContinueButton) {
            gcashModalContinueButton.addEventListener("click", function () {
                if (activeGcashModalMode !== "confirm-booking") {
                    return;
                }

                if (!pendingGcashOrderRecord) {
                    closeGcashModal();
                    return;
                }

                handlePendingOrderSubmission(pendingGcashOrderRecord);
            });
        }

        if (gcashUploadButton) {
            gcashUploadButton.addEventListener("click", function () {
                if (activeGcashModalMode !== "upload-receipt") {
                    return;
                }

                var orderId = String(activeGcashUploadOrderId || "").trim();
                if (!orderId) {
                    setGcashUploadMessage("Order reference is missing.", true);
                    return;
                }

                var targetOrder = findStoredOrderById(orderId);
                var countdownState = getOrderPaymentReceiptCountdownState(targetOrder);

                if (countdownState.active && countdownState.expired) {
                    if (applyLocalPaymentReceiptTimeouts()) {
                        renderOrderStatusList();
                    }

                    closeGcashModal();
                    return;
                }

                var customerGcashName = String(customerGcashInfo.gcashName || "").trim();
                var customerGcashNumber = String(customerGcashInfo.gcashNumber || "").trim();

                if (!gcashReceiptFileInput || !gcashReceiptFileInput.files || !gcashReceiptFileInput.files.length) {
                    setGcashUploadMessage("Please select a receipt image first.", true);
                    return;
                }

                if (isUploadingGcashReceipt) {
                    return;
                }

                var selectedFile = gcashReceiptFileInput.files[0];
                var reader = new FileReader();

                isUploadingGcashReceipt = true;
                gcashUploadButton.disabled = true;

                if (gcashReceiptSelectButton) {
                    gcashReceiptSelectButton.disabled = true;
                }

                setGcashUploadMessage("Uploading payment receipt...", false);

                reader.onload = function (loadEvent) {
                    var imageDataUrl = String(loadEvent && loadEvent.target && loadEvent.target.result ? loadEvent.target.result : "");

                    if (imageDataUrl.indexOf("data:image/") !== 0) {
                        setGcashUploadMessage("Please upload a valid image file.", true);
                        isUploadingGcashReceipt = false;
                        gcashUploadButton.disabled = false;

                        if (gcashReceiptSelectButton) {
                            gcashReceiptSelectButton.disabled = false;
                        }

                        return;
                    }

                    submitOrderReceiptUpload(orderId, imageDataUrl, customerGcashName, customerGcashNumber)
                        .then(function (responsePayload) {
                            var savedOrder = normalizeStoredOrder(responsePayload.order || null);

                            customerGcashInfo = normalizeCustomerGcashInfo(
                                responsePayload && responsePayload.customerGcashProfile
                                    ? responsePayload.customerGcashProfile
                                    : {
                                        gcashName: customerGcashName,
                                        gcashNumber: customerGcashNumber
                                    }
                            );

                            if (!savedOrder) {
                                throw new Error("Unable to refresh reservation receipt status.");
                            }

                            var existingOrders = getStoredOrders();
                            var hasMatch = false;
                            var nextOrders = existingOrders.map(function (order) {
                                if (order.id === savedOrder.id) {
                                    hasMatch = true;
                                    return savedOrder;
                                }

                                return order;
                            });

                            if (!hasMatch) {
                                nextOrders.unshift(savedOrder);
                            }

                            saveStoredOrders(nextOrders);
                            renderOrderStatusList();
                            closeGcashModal();

                            if (bookingNote) {
                                bookingNote.textContent = "Payment receipt uploaded. Waiting for admin review.";
                            }

                            showCartToast("Payment receipt uploaded");
                            setCartView("order-status");
                        })
                        .catch(function (error) {
                            var uploadErrorMessage = error && error.message
                                ? String(error.message)
                                : "Unable to upload payment receipt right now.";

                            setGcashUploadMessage(uploadErrorMessage, true);

                            if (bookingNote) {
                                bookingNote.textContent = uploadErrorMessage;
                            }
                        })
                        .finally(function () {
                            isUploadingGcashReceipt = false;
                            gcashUploadButton.disabled = false;

                            if (gcashReceiptSelectButton) {
                                gcashReceiptSelectButton.disabled = false;
                            }
                        });
                };

                reader.onerror = function () {
                    setGcashUploadMessage("Unable to read the selected image.", true);
                    isUploadingGcashReceipt = false;
                    gcashUploadButton.disabled = false;

                    if (gcashReceiptSelectButton) {
                        gcashReceiptSelectButton.disabled = false;
                    }
                };

                reader.readAsDataURL(selectedFile);
            });
        }

        if (gcashModalQrImage) {
            gcashModalQrImage.addEventListener("error", function () {
                gcashModalQrImage.hidden = true;

                if (gcashModalQrEmpty) {
                    gcashModalQrEmpty.textContent = "Unable to load GCash QR right now. Please contact Rental Services.";
                    gcashModalQrEmpty.hidden = false;
                }
            });
        }

        if (orderCancelConfirmButton) {
            orderCancelConfirmButton.addEventListener("click", function () {
                var targetOrderId = String(activeCancelOrderId || "").trim();
                var reasonValue = orderCancelReasonInput ? String(orderCancelReasonInput.value || "").trim() : "";

                if (!targetOrderId) {
                    closeOrderCancelModal();
                    return;
                }

                if (reasonValue === "") {
                    setOrderCancelError("Please provide a cancellation reason.");

                    if (orderCancelReasonInput) {
                        orderCancelReasonInput.focus();
                    }

                    return;
                }

                if (orderCancelConfirmButton.disabled) {
                    return;
                }

                orderCancelConfirmButton.disabled = true;
                setOrderCancelError("");

                submitOrderCancellation(targetOrderId, reasonValue)
                    .then(function (responsePayload) {
                        var savedOrder = normalizeStoredOrder(responsePayload.order || null);

                        if (!savedOrder) {
                            throw new Error("Unable to refresh canceled reservation.");
                        }

                        var existingOrders = getStoredOrders();
                        var hasMatch = false;
                        var nextOrders = existingOrders.map(function (order) {
                            if (order.id === savedOrder.id) {
                                hasMatch = true;
                                return savedOrder;
                            }

                            return order;
                        });

                        if (!hasMatch) {
                            nextOrders.unshift(savedOrder);
                        }

                        saveStoredOrders(nextOrders);
                        renderOrderStatusList();
                        closeOrderCancelModal();

                        if (bookingNote) {
                            bookingNote.textContent = "Reservation canceled successfully.";
                        }

                        showCartToast("Reservation canceled");
                        setCartView("order-status");
                    })
                    .catch(function (error) {
                        setOrderCancelError(error && error.message
                            ? String(error.message)
                            : "Unable to cancel your reservation right now.");
                    })
                    .finally(function () {
                        orderCancelConfirmButton.disabled = false;
                    });
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && orderCancelModal && !orderCancelModal.hidden) {
                closeOrderCancelModal();
                return;
            }

            if (event.key === "Escape" && refundProofModal && !refundProofModal.hidden) {
                closeRefundProofModal();
                return;
            }

            if (event.key === "Escape" && deliveryProofModal && !deliveryProofModal.hidden) {
                closeDeliveryProofModal();
                return;
            }

            if (event.key === "Escape" && deliveryUploadModal && !deliveryUploadModal.hidden) {
                if (!isUploadingDeliveryReceipt) {
                    closeDeliveryUploadModal();
                }
                return;
            }

            if (event.key === "Escape" && customerNotificationModal && !customerNotificationModal.hidden) {
                closeCustomerNotificationModal();
                return;
            }

            if (event.key === "Escape" && gcashModal && !gcashModal.hidden) {
                closeGcashModal();
            }
        });

        cartNavButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                setCartView(button.getAttribute("data-cart-nav") || "cart");
            });
        });

        saveCustomerNotifications(customerNotifications);
        setCustomerNotificationBadgeCount(customerNotificationUnreadCount);
        renderCustomerNotificationList();

        function getMethodValue(inputList, fallbackValue) {
            var checked = Array.prototype.find.call(inputList, function (input) {
                return input.checked;
            });

            return checked ? checked.value : fallbackValue;
        }

        function normalizeCartEquipmentAvailabilityPayload(rawValue) {
            var source = rawValue && typeof rawValue === "object" ? rawValue : {};
            var bookingSource = source.booking && typeof source.booking === "object"
                ? source.booking
                : {};
            var productsSource = source.products && typeof source.products === "object"
                ? source.products
                : {};
            var reservationsSource = Array.isArray(source.reservations) ? source.reservations : [];
            var capacities = {};
            var reservationsByProduct = {};
            var horizonParsed = Number.parseInt(source.horizonDays, 10);
            var horizonDays = Number.isFinite(horizonParsed) ? horizonParsed : 730;

            function normalizeHour(value, fallbackValue) {
                var parsedValue = Number.parseInt(value, 10);

                if (!Number.isFinite(parsedValue)) {
                    return fallbackValue;
                }

                return Math.max(0, Math.min(23, parsedValue));
            }

            var openHour = normalizeHour(bookingSource.openHour, 8);
            var closeHour = normalizeHour(bookingSource.closeHour, 17);
            var sameDayCutoffHour = normalizeHour(bookingSource.sameDayCutoffHour, 15);
            var leadHoursParsed = Number.parseInt(bookingSource.leadHours, 10);
            var leadHours = Number.isFinite(leadHoursParsed) && leadHoursParsed >= 0
                ? leadHoursParsed
                : 2;

            if (closeHour < openHour) {
                closeHour = openHour;
            }

            horizonDays = Math.max(30, Math.min(1095, horizonDays));

            Object.keys(productsSource).forEach(function (productKeyRaw) {
                var normalizedProductKey = String(productKeyRaw || "").trim().toLowerCase();
                if (!normalizedProductKey) {
                    return;
                }

                var productRecord = productsSource[productKeyRaw];
                var capacityParsed = Number.parseInt(productRecord && productRecord.capacity, 10);
                var capacity = Number.isFinite(capacityParsed) ? capacityParsed : 0;

                capacities[normalizedProductKey] = Math.max(0, capacity);
            });

            reservationsSource.forEach(function (reservation) {
                if (!reservation || typeof reservation !== "object") {
                    return;
                }

                var productKey = String(reservation.productKey || "").trim().toLowerCase();
                var qtyParsed = Number.parseInt(reservation.qty, 10);
                var daysParsed = Number.parseInt(reservation.days, 10);
                var qty = Number.isFinite(qtyParsed) && qtyParsed > 0 ? qtyParsed : 1;
                var days = Number.isFinite(daysParsed) && daysParsed > 0 ? daysParsed : 1;
                var startDate = String(reservation.startDate || "").trim();
                var startTime = String(reservation.startTime || "").trim();
                var startTimestamp = parseCartLocalScheduleTimestamp(startDate, startTime);
                var endDate = String(reservation.endDate || "").trim();
                var endTime = String(reservation.endTime || "").trim();
                var endTimestamp = parseCartLocalScheduleTimestamp(endDate, endTime);

                if (!productKey || !Number.isFinite(startTimestamp)) {
                    return;
                }

                if (!reservationsByProduct[productKey]) {
                    reservationsByProduct[productKey] = [];
                }

                reservationsByProduct[productKey].push({
                    qty: qty,
                    startTs: startTimestamp,
                    endTs: Number.isFinite(endTimestamp) && endTimestamp > startTimestamp
                        ? endTimestamp
                        : (startTimestamp + (days * 24 * 60 * 60 * 1000))
                });
            });

            return {
                booking: {
                    openHour: openHour,
                    closeHour: closeHour,
                    sameDayCutoffHour: sameDayCutoffHour,
                    leadHours: leadHours
                },
                capacities: capacities,
                reservationsByProduct: reservationsByProduct,
                horizonDays: horizonDays
            };
        }

        function parseCartDateKey(dateKey) {
            var normalized = String(dateKey || "").trim();
            var matches = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);

            if (!matches) {
                return null;
            }

            var year = Number.parseInt(matches[1], 10);
            var month = Number.parseInt(matches[2], 10);
            var day = Number.parseInt(matches[3], 10);
            var parsedDate = new Date(year, month - 1, day);

            if (
                Number.isNaN(parsedDate.getTime())
                || parsedDate.getFullYear() !== year
                || (parsedDate.getMonth() + 1) !== month
                || parsedDate.getDate() !== day
            ) {
                return null;
            }

            return {
                year: year,
                month: month,
                day: day
            };
        }

        function parseCartLocalScheduleTimestamp(dateKey, timeValue) {
            var dateParts = parseCartDateKey(dateKey);
            var hour = parseCartBookingSlotHour(timeValue);

            if (!dateParts || !Number.isFinite(hour)) {
                return Number.NaN;
            }

            var parsedDate = new Date(dateParts.year, dateParts.month - 1, dateParts.day, hour, 0, 0, 0);

            if (
                Number.isNaN(parsedDate.getTime())
                || parsedDate.getFullYear() !== dateParts.year
                || (parsedDate.getMonth() + 1) !== dateParts.month
                || parsedDate.getDate() !== dateParts.day
                || parsedDate.getHours() !== hour
            ) {
                return Number.NaN;
            }

            return parsedDate.getTime();
        }

        function addDaysToDateKey(dateKey, dayOffset) {
            var dateParts = parseCartDateKey(dateKey);

            if (!dateParts) {
                return "";
            }

            var shiftedDate = new Date(dateParts.year, dateParts.month - 1, dateParts.day + dayOffset);

            if (Number.isNaN(shiftedDate.getTime())) {
                return "";
            }

            return dateKeyFromDate(shiftedDate);
        }

        function dateFromCartDateKey(dateKey) {
            var parts = parseCartDateKey(dateKey);

            if (!parts) {
                return null;
            }

            var parsedDate = new Date(parts.year, parts.month - 1, parts.day);

            if (Number.isNaN(parsedDate.getTime())) {
                return null;
            }

            return parsedDate;
        }

        function monthIndexFromDate(dateValue) {
            if (!(dateValue instanceof Date) || Number.isNaN(dateValue.getTime())) {
                return 0;
            }

            return (dateValue.getFullYear() * 12) + dateValue.getMonth();
        }

        function syncReceiveDateDisplay() {
            if (!receiveDateDisplay || !receiveDateInput) {
                return;
            }

            var selectedDate = String(receiveDateInput.value || "").trim();

            if (!selectedDate) {
                receiveDateDisplay.textContent = "Select a receiving date";
                receiveDateDisplay.classList.add("is-empty");
                receiveDateDisplay.classList.remove("is-selected");
                return;
            }

            receiveDateDisplay.textContent = formatDateDisplay(selectedDate);
            receiveDateDisplay.classList.remove("is-empty");
            receiveDateDisplay.classList.add("is-selected");
        }

        function clampReceiveDateCalendarCursor(context) {
            if (!(receiveDateCalendarCursor instanceof Date) || Number.isNaN(receiveDateCalendarCursor.getTime())) {
                var minimumDateForCursor = dateFromCartDateKey(context.minimumDateKey);
                receiveDateCalendarCursor = minimumDateForCursor
                    ? new Date(minimumDateForCursor.getFullYear(), minimumDateForCursor.getMonth(), 1)
                    : new Date();
            }

            var minimumDate = dateFromCartDateKey(context.minimumDateKey);

            if (!minimumDate) {
                return;
            }

            var minimumMonth = new Date(minimumDate.getFullYear(), minimumDate.getMonth(), 1);
            var maximumDate = new Date(
                minimumDate.getFullYear(),
                minimumDate.getMonth(),
                minimumDate.getDate() + cartBookingAvailabilityHorizonDays
            );
            var maximumMonth = new Date(maximumDate.getFullYear(), maximumDate.getMonth(), 1);
            var cursorMonthIndex = monthIndexFromDate(receiveDateCalendarCursor);
            var minimumMonthIndex = monthIndexFromDate(minimumMonth);
            var maximumMonthIndex = monthIndexFromDate(maximumMonth);

            if (cursorMonthIndex < minimumMonthIndex) {
                receiveDateCalendarCursor = minimumMonth;
                return;
            }

            if (cursorMonthIndex > maximumMonthIndex) {
                receiveDateCalendarCursor = maximumMonth;
            }
        }

        function updateReceiveDateCalendarNavState(context) {
            if (!receiveDateCalendarNavButtons.length) {
                return;
            }

            var minimumDate = dateFromCartDateKey(context.minimumDateKey);

            if (!minimumDate) {
                return;
            }

            var minimumMonthIndex = monthIndexFromDate(new Date(minimumDate.getFullYear(), minimumDate.getMonth(), 1));
            var maximumDate = new Date(
                minimumDate.getFullYear(),
                minimumDate.getMonth(),
                minimumDate.getDate() + cartBookingAvailabilityHorizonDays
            );
            var maximumMonthIndex = monthIndexFromDate(new Date(maximumDate.getFullYear(), maximumDate.getMonth(), 1));
            var cursorMonthIndex = monthIndexFromDate(receiveDateCalendarCursor);

            receiveDateCalendarNavButtons.forEach(function (button) {
                var direction = String(button.getAttribute("data-receive-calendar-nav") || "").trim().toLowerCase();

                if (direction === "prev") {
                    button.disabled = cursorMonthIndex <= minimumMonthIndex;
                    return;
                }

                if (direction === "next") {
                    button.disabled = cursorMonthIndex >= maximumMonthIndex;
                }
            });
        }

        function normalizeCartCameraProductKey(value) {
            var normalized = String(value || "").trim().toLowerCase();

            if (!normalized) {
                return "";
            }

            normalized = normalized.replace(/[^a-z0-9-]+/g, "-").replace(/^-+|-+$/g, "");

            return normalized;
        }

        function resolvePackageCameraField(source, primaryKey, fallbackKeys) {
            if (!source || typeof source !== "object") {
                return "";
            }

            var keyList = [primaryKey];

            if (Array.isArray(fallbackKeys)) {
                keyList = keyList.concat(fallbackKeys);
            }

            for (var index = 0; index < keyList.length; index += 1) {
                var key = String(keyList[index] || "").trim();

                if (!key || !Object.prototype.hasOwnProperty.call(source, key)) {
                    continue;
                }

                return String(source[key] || "").trim();
            }

            return "";
        }

        function parseCartPackageCameraCatalog(rawCatalog) {
            var sourceCatalog = rawCatalog;

            if (typeof sourceCatalog === "string") {
                try {
                    sourceCatalog = JSON.parse(sourceCatalog);
                } catch (error) {
                    sourceCatalog = {};
                }
            }

            if (!sourceCatalog || typeof sourceCatalog !== "object") {
                return {};
            }

            var normalizedCatalog = {};

            Object.keys(sourceCatalog).forEach(function (rawPackageKey) {
                var packageKey = normalizeCartCameraProductKey(rawPackageKey);

                if (!packageKey) {
                    return;
                }

                var packageRecord = sourceCatalog[rawPackageKey];

                if (!packageRecord || typeof packageRecord !== "object") {
                    return;
                }

                normalizedCatalog[packageKey] = {
                    camera1: normalizeCartCameraProductKey(resolvePackageCameraField(packageRecord, "camera1", ["camera_1"])),
                    camera2: normalizeCartCameraProductKey(resolvePackageCameraField(packageRecord, "camera2", ["camera_2"])),
                    backupCamera1: normalizeCartCameraProductKey(resolvePackageCameraField(packageRecord, "backupCamera1", ["backup_camera_1", "backupcamera1"])),
                    backupCamera2: normalizeCartCameraProductKey(resolvePackageCameraField(packageRecord, "backupCamera2", ["backup_camera_2", "backupcamera2"]))
                };
            });

            return normalizedCatalog;
        }

        var cartEventPackageCameraCatalog = parseCartPackageCameraCatalog(window.__creatyEventPackageCameras);
        var cartServicePackageCameraCatalog = parseCartPackageCameraCatalog(window.__creatyServicePackageCameras);

        function resolveCartPackageKeyFromItem(item, expectedType, idPrefix) {
            if (!item || typeof item !== "object") {
                return "";
            }

            var itemType = String(item.type || item.itemType || item.item_type || "").trim().toLowerCase();

            if (itemType && itemType !== expectedType) {
                return "";
            }

            var explicitPackageKey = normalizeCartCameraProductKey(
                item.packageKey
                || item.package_key
                || item.package
                || (expectedType === "event-package" ? (item.eventPackageKey || item.event_package_key) : "")
                || (expectedType === "service-package" ? (item.servicePackageKey || item.service_package_key) : "")
                || ""
            );

            if (explicitPackageKey) {
                return explicitPackageKey;
            }

            var itemId = String(item.id || item.itemId || item.item_id || "").trim().toLowerCase();

            if (itemId.indexOf(idPrefix) !== 0) {
                return "";
            }

            return normalizeCartCameraProductKey(itemId.slice(idPrefix.length));
        }

        function getCartPackageCameraProductKeys(item) {
            if (!item || typeof item !== "object") {
                return [];
            }

            var cameraProductKeys = [];
            var eventPackageKey = resolveCartPackageKeyFromItem(item, "event-package", "event-");
            var servicePackageKey = resolveCartPackageKeyFromItem(item, "service-package", "service-");

            if (eventPackageKey && cartEventPackageCameraCatalog[eventPackageKey]) {
                var eventCameraConfig = cartEventPackageCameraCatalog[eventPackageKey];

                if (eventCameraConfig.camera1) {
                    cameraProductKeys.push(eventCameraConfig.camera1);
                }

                if (eventCameraConfig.camera2) {
                    cameraProductKeys.push(eventCameraConfig.camera2);
                }

                if (eventCameraConfig.backupCamera1) {
                    cameraProductKeys.push(eventCameraConfig.backupCamera1);
                }

                if (eventCameraConfig.backupCamera2) {
                    cameraProductKeys.push(eventCameraConfig.backupCamera2);
                }
            }

            if (servicePackageKey && cartServicePackageCameraCatalog[servicePackageKey]) {
                var serviceCameraConfig = cartServicePackageCameraCatalog[servicePackageKey];

                if (serviceCameraConfig.camera1) {
                    cameraProductKeys.push(serviceCameraConfig.camera1);
                }

                if (serviceCameraConfig.camera2) {
                    cameraProductKeys.push(serviceCameraConfig.camera2);
                }

                if (serviceCameraConfig.backupCamera1) {
                    cameraProductKeys.push(serviceCameraConfig.backupCamera1);
                }

                if (serviceCameraConfig.backupCamera2) {
                    cameraProductKeys.push(serviceCameraConfig.backupCamera2);
                }
            }

            return cameraProductKeys;
        }

        function extractCameraProductKeyFromCartItem(item) {
            if (!item || typeof item !== "object") {
                return "";
            }

            var itemType = String(item.type || item.itemType || item.item_type || "").trim().toLowerCase();

            if (itemType && itemType !== "camera") {
                return "";
            }

            var productKey = normalizeCartCameraProductKey(item.productKey || item.product_key || "");

            if (!productKey) {
                var itemId = String(item.id || item.itemId || item.item_id || "").trim().toLowerCase();

                if (itemId.indexOf("camera-") === 0) {
                    productKey = normalizeCartCameraProductKey(itemId.slice(7));
                }
            }

            return productKey;
        }

        function extractCameraProductKeysFromCartItem(item) {
            var productKeys = [];
            var directCameraProductKey = extractCameraProductKeyFromCartItem(item);

            if (directCameraProductKey) {
                productKeys.push(directCameraProductKey);
            }

            var packageCameraProductKeys = getCartPackageCameraProductKeys(item);

            packageCameraProductKeys.forEach(function (productKey) {
                var normalizedProductKey = normalizeCartCameraProductKey(productKey);

                if (normalizedProductKey) {
                    productKeys.push(normalizedProductKey);
                }
            });

            return productKeys;
        }

        var equipmentAvailability = normalizeCartEquipmentAvailabilityPayload(window.__creatyEquipmentAvailability);
        var cartBookingOpenHour = equipmentAvailability.booking.openHour;
        var cartBookingCloseHour = equipmentAvailability.booking.closeHour;
        var cartBookingSameDayCutoffHour = equipmentAvailability.booking.sameDayCutoffHour;
        var cartBookingLeadHours = equipmentAvailability.booking.leadHours;
        var cartBookingAvailabilityHorizonDays = equipmentAvailability.horizonDays;
        var cartBookingTopHourReloadTimerId = null;

        setCartView(customerInitialView, {
            persist: false
        });

        function parseCartBookingSlotHour(timeValue) {
            var normalized = String(timeValue || "").trim();
            var matches = normalized.match(/^(\d{2}):(\d{2})$/);

            if (!matches) {
                return null;
            }

            var hour = Number.parseInt(matches[1], 10);
            var minute = Number.parseInt(matches[2], 10);

            if (!Number.isFinite(hour) || !Number.isFinite(minute) || minute !== 0) {
                return null;
            }

            return hour;
        }

        function getCartReceivingMinimumHour() {
            if (isServicesCartViewActive()) {
                return 8;
            }

            var selectedReceivingMethod = getMethodValue(receiveMethodInputs, "pickup");
            var minimumHour = cartBookingOpenHour;

            if (selectedReceivingMethod === "pickup") {
                minimumHour = Math.max(minimumHour, 9);
            }

            return minimumHour;
        }

        function buildCartCameraRequirements(sourceItems) {
            var source = Array.isArray(sourceItems) ? sourceItems : getActiveCartItems();
            var requirements = {};

            source.forEach(function (item) {
                if (!item || isUnavailableCartItem(item)) {
                    return;
                }

                var productKeys = extractCameraProductKeysFromCartItem(item);

                if (!productKeys.length) {
                    return;
                }

                var qtyParsed = Number.parseInt(item.qty, 10);
                var daysParsed = Number.parseInt(item.days, 10);
                var qty = Number.isFinite(qtyParsed) && qtyParsed > 0 ? qtyParsed : 1;
                var days = Number.isFinite(daysParsed) && daysParsed > 0 ? daysParsed : 1;

                productKeys.forEach(function (productKey) {
                    if (!requirements[productKey]) {
                        requirements[productKey] = {
                            qty: 0,
                            days: 0
                        };
                    }

                    requirements[productKey].qty += qty;
                    requirements[productKey].days = Math.max(requirements[productKey].days, days);
                });
            });

            return requirements;
        }

        function isCartSlotWithinPolicy(dateKey, slotHour, context) {
            if (!Number.isFinite(slotHour)) {
                return false;
            }

            if (slotHour < getCartReceivingMinimumHour() || slotHour > cartBookingCloseHour) {
                return false;
            }

            if (!dateKey || dateKey < context.minimumDateKey) {
                return false;
            }

            if (dateKey === context.todayKey && slotHour < context.sameDayMinimumHour) {
                return false;
            }

            return true;
        }

        function isCartScheduleAvailableForRequirements(dateKey, timeValue, requirements) {
            var startTimestamp = parseCartLocalScheduleTimestamp(dateKey, timeValue);

            if (!Number.isFinite(startTimestamp)) {
                return false;
            }

            var servicesCartEndTimestamp = null;

            if (isServicesCartViewActive()) {
                var serviceDuration = getServiceDurationConfig();
                var durationMs = serviceDuration.unit === "days"
                    ? (serviceDuration.value * 24 * 60 * 60 * 1000)
                    : (serviceDuration.value * 60 * 60 * 1000);

                servicesCartEndTimestamp = startTimestamp + durationMs;

                if (!Number.isFinite(servicesCartEndTimestamp) || servicesCartEndTimestamp <= startTimestamp) {
                    servicesCartEndTimestamp = null;
                }
            }

            var requirementKeys = Object.keys(requirements || {});

            if (!requirementKeys.length) {
                return true;
            }

            return requirementKeys.every(function (productKey) {
                var requirement = requirements[productKey] || {};
                var requiredQty = Number.parseInt(requirement.qty, 10);
                var requiredDays = Number.parseInt(requirement.days, 10);

                requiredQty = Number.isFinite(requiredQty) && requiredQty > 0 ? requiredQty : 1;
                requiredDays = Number.isFinite(requiredDays) && requiredDays > 0 ? requiredDays : 1;

                var capacityParsed = Number.parseInt(equipmentAvailability.capacities[productKey], 10);
                var capacity = Number.isFinite(capacityParsed)
                    ? Math.max(0, capacityParsed)
                    : 1;

                if (requiredQty > capacity) {
                    return false;
                }

                var requiredEnd = Number.isFinite(servicesCartEndTimestamp)
                    ? servicesCartEndTimestamp
                    : (startTimestamp + (requiredDays * 24 * 60 * 60 * 1000));
                var intervals = Array.isArray(equipmentAvailability.reservationsByProduct[productKey])
                    ? equipmentAvailability.reservationsByProduct[productKey]
                    : [];
                var occupiedQty = peakReservedQuantity(intervals, startTimestamp, requiredEnd);

                return (occupiedQty + requiredQty) <= capacity;
            });
        }

        function getAvailableReceiveSlotsForDate(dateKey, requirements, context) {
            if (!receiveTimeSelect) {
                return [];
            }

            var slots = [];

            Array.prototype.forEach.call(receiveTimeSelect.options, function (option) {
                var slotHour = parseCartBookingSlotHour(option.value);

                if (!isCartSlotWithinPolicy(dateKey, slotHour, context)) {
                    return;
                }

                if (!isCartScheduleAvailableForRequirements(dateKey, option.value, requirements)) {
                    return;
                }

                slots.push(option.value);
            });

            return slots;
        }

        function findNextAvailableReceiveSchedule(requirements, preferredDateKey, context) {
            var startingDateKey = preferredDateKey && preferredDateKey >= context.minimumDateKey
                ? preferredDateKey
                : context.minimumDateKey;

            var normalizedStartKey = addDaysToDateKey(startingDateKey, 0);

            if (!normalizedStartKey) {
                normalizedStartKey = addDaysToDateKey(context.minimumDateKey, 0);
            }

            if (!normalizedStartKey) {
                return null;
            }

            for (var dayOffset = 0; dayOffset <= cartBookingAvailabilityHorizonDays; dayOffset += 1) {
                var candidateDateKey = addDaysToDateKey(normalizedStartKey, dayOffset);

                if (!candidateDateKey) {
                    continue;
                }

                var availableSlots = getAvailableReceiveSlotsForDate(candidateDateKey, requirements, context);

                if (!availableSlots.length) {
                    continue;
                }

                return {
                    dateKey: candidateDateKey,
                    timeValue: availableSlots[0],
                    slots: availableSlots
                };
            }

            return null;
        }

        function renderReceiveDateCalendar(items) {
            if (!receiveDateCalendarGrid || !receiveDateInput) {
                syncReceiveDateDisplay();
                return;
            }

            var context = getCartBookingContext();
            var requirements = buildCartCameraRequirements(items);
            var selectedDate = String(receiveDateInput.value || "").trim();
            var minimumDate = dateFromCartDateKey(context.minimumDateKey);

            if (!selectedDate || selectedDate < context.minimumDateKey) {
                selectedDate = context.minimumDateKey;
            }

            if (!(receiveDateCalendarCursor instanceof Date) || Number.isNaN(receiveDateCalendarCursor.getTime())) {
                var selectedDateObject = dateFromCartDateKey(selectedDate);
                var seedDate = selectedDateObject || minimumDate || new Date();
                receiveDateCalendarCursor = new Date(seedDate.getFullYear(), seedDate.getMonth(), 1);
            }

            clampReceiveDateCalendarCursor(context);

            var monthLabelDate = new Date(
                receiveDateCalendarCursor.getFullYear(),
                receiveDateCalendarCursor.getMonth(),
                1
            );

            if (receiveDateCalendarTitle) {
                receiveDateCalendarTitle.textContent = monthLabelDate.toLocaleDateString("en-US", {
                    month: "long",
                    year: "numeric"
                });
            }

            receiveDateCalendarGrid.innerHTML = "";

            receiveCalendarWeekdays.forEach(function (weekdayLabel) {
                var weekdayNode = document.createElement("span");
                weekdayNode.className = "cart-receive-calendar-day-name";
                weekdayNode.textContent = weekdayLabel;
                receiveDateCalendarGrid.appendChild(weekdayNode);
            });

            var year = receiveDateCalendarCursor.getFullYear();
            var month = receiveDateCalendarCursor.getMonth();
            var firstWeekday = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();

            for (var leadingIndex = 0; leadingIndex < firstWeekday; leadingIndex += 1) {
                var leadingNode = document.createElement("span");
                leadingNode.className = "date-calendar-cell is-empty";
                leadingNode.setAttribute("aria-hidden", "true");
                receiveDateCalendarGrid.appendChild(leadingNode);
            }

            for (var day = 1; day <= daysInMonth; day += 1) {
                var dayDate = new Date(year, month, day);
                var dateKey = dateKeyFromDate(dayDate);
                var availableSlots = getAvailableReceiveSlotsForDate(dateKey, requirements, context);
                var isAvailable = availableSlots.length > 0;
                var isPastOrBlocked = dateKey < context.minimumDateKey;
                var dayButton = document.createElement("button");

                dayButton.type = "button";
                dayButton.className = "date-calendar-cell date-calendar-day";
                dayButton.textContent = String(day);
                dayButton.setAttribute("data-receive-calendar-date", dateKey);
                dayButton.setAttribute("aria-label", dayDate.toLocaleDateString("en-US", {
                    month: "long",
                    day: "numeric",
                    year: "numeric"
                }));

                if (isPastOrBlocked) {
                    dayButton.classList.add("is-past", "is-unavailable");
                    dayButton.disabled = true;
                } else if (isAvailable) {
                    dayButton.classList.add("is-available");

                    if (selectedDate === dateKey) {
                        dayButton.classList.add("is-selected");
                    }
                } else {
                    dayButton.classList.add("is-unavailable");
                    dayButton.disabled = true;
                }

                receiveDateCalendarGrid.appendChild(dayButton);
            }

            updateReceiveDateCalendarNavState(context);
            syncReceiveDateDisplay();

            if (receiveDateCalendarNote) {
                receiveDateCalendarNote.hidden = findNextAvailableReceiveSchedule(requirements, context.minimumDateKey, context) !== null;
            }
        }

        function getCartBookingContext() {
            var now = new Date();
            var todayDate = getStartOfDay(now);
            var todayKey = dateKeyFromDate(todayDate);
            var currentHour = now.getHours();

            var selectedMinimumHour = getCartReceivingMinimumHour();
            var minimumDate = new Date(
                todayDate.getFullYear(),
                todayDate.getMonth(),
                todayDate.getDate() + (currentHour >= cartBookingSameDayCutoffHour ? 1 : 0)
            );

            return {
                todayKey: todayKey,
                currentHour: currentHour,
                minimumDateKey: dateKeyFromDate(minimumDate),
                sameDayMinimumHour: Math.max(selectedMinimumHour, currentHour + cartBookingLeadHours)
            };
        }

        function syncReceiveDateConstraints(items) {
            if (!receiveDateInput) {
                return;
            }

            var context = getCartBookingContext();
            var requirements = buildCartCameraRequirements(items);
            var selectedDate = String(receiveDateInput.value || "").trim();

            receiveDateInput.min = context.minimumDateKey;

            if (!selectedDate || selectedDate < context.minimumDateKey) {
                selectedDate = context.minimumDateKey;
            }

            var dateSlots = getAvailableReceiveSlotsForDate(selectedDate, requirements, context);

            if (!dateSlots.length) {
                var fallbackSchedule = findNextAvailableReceiveSchedule(requirements, selectedDate, context);

                if (fallbackSchedule) {
                    selectedDate = fallbackSchedule.dateKey;

                    if (receiveTimeSelect && fallbackSchedule.timeValue) {
                        receiveTimeSelect.value = fallbackSchedule.timeValue;
                    }
                }
            }

            receiveDateInput.value = selectedDate;
        }

        function syncReceiveTimeConstraints(items) {
            if (!receiveDateInput || !receiveTimeSelect) {
                return;
            }

            var context = getCartBookingContext();
            var requirements = buildCartCameraRequirements(items);
            var selectedDate = String(receiveDateInput.value || "").trim();
            var availableSlots = getAvailableReceiveSlotsForDate(selectedDate, requirements, context);

            if (!availableSlots.length) {
                var fallbackSchedule = findNextAvailableReceiveSchedule(requirements, selectedDate, context);

                if (fallbackSchedule) {
                    selectedDate = fallbackSchedule.dateKey;
                    receiveDateInput.value = selectedDate;
                    availableSlots = Array.isArray(fallbackSchedule.slots) ? fallbackSchedule.slots : [];
                }
            }

            var firstValidSlotValue = "";
            var hasSelectedValidSlot = false;

            Array.prototype.forEach.call(receiveTimeSelect.options, function (option) {
                var isValidSlot = availableSlots.indexOf(option.value) >= 0;

                option.disabled = !isValidSlot;

                if (isValidSlot && firstValidSlotValue === "") {
                    firstValidSlotValue = option.value;
                }

                if (isValidSlot && option.value === receiveTimeSelect.value) {
                    hasSelectedValidSlot = true;
                }
            });

            if (!hasSelectedValidSlot) {
                receiveTimeSelect.value = firstValidSlotValue;
            }
        }

        function hasValidCurrentReceiveSchedule(items) {
            if (!receiveDateInput || !receiveTimeSelect) {
                return true;
            }

            var selectedDate = String(receiveDateInput.value || "").trim();
            var selectedTime = String(receiveTimeSelect.value || "").trim();

            if (!selectedDate || !selectedTime) {
                return false;
            }

            var context = getCartBookingContext();
            var requirements = buildCartCameraRequirements(items);
            var availableSlots = getAvailableReceiveSlotsForDate(selectedDate, requirements, context);

            return availableSlots.indexOf(selectedTime) >= 0;
        }

        function enforceReceiveScheduleConstraints(items) {
            syncReceiveDateConstraints(items);
            syncReceiveTimeConstraints(items);
            renderReceiveDateCalendar(items);
        }

        function scheduleCartTopOfHourReload() {
            if (!bookingCard) {
                return;
            }

            if (cartBookingTopHourReloadTimerId !== null) {
                window.clearTimeout(cartBookingTopHourReloadTimerId);
                cartBookingTopHourReloadTimerId = null;
            }

            var now = new Date();
            var msUntilNextHour = ((59 - now.getMinutes()) * 60 + (59 - now.getSeconds())) * 1000
                + (1000 - now.getMilliseconds());

            if (!Number.isFinite(msUntilNextHour) || msUntilNextHour < 1) {
                msUntilNextHour = 1000;
            }

            cartBookingTopHourReloadTimerId = window.setTimeout(function () {
                window.location.reload();
            }, msUntilNextHour);
        }

        function restoreBookingDefaults() {
            var context = getCartBookingContext();

            if (receiveDateInput) {
                receiveDateInput.value = bookingState.receiveDate || context.minimumDateKey;
            }

            if (placeSelect) {
                if (bookingState.place) {
                    placeSelect.value = bookingState.place;
                }

                if (!placeSelect.value && placeSelect.options.length) {
                    placeSelect.selectedIndex = 0;
                }
            }

            if (eventPlaceInput) {
                var storedPlaceValue = String(bookingState.place || "").trim();

                if (!storedPlaceValue && placeSelect) {
                    storedPlaceValue = String(placeSelect.value || "").trim();
                }

                eventPlaceInput.value = storedPlaceValue;
            }

            if (courierSelect && bookingState.courier) {
                courierSelect.value = bookingState.courier;
            }

            if (receiveTimeSelect && bookingState.receiveTime) {
                receiveTimeSelect.value = bookingState.receiveTime;
            }

            enforceReceiveScheduleConstraints(getActiveCartItems());

            if (returnDateInput) {
                returnDateInput.min = receiveDateInput ? receiveDateInput.value : context.minimumDateKey;
            }

            if (paymentSelect) {
                if (bookingState.paymentMethod) {
                    paymentSelect.value = bookingState.paymentMethod;
                }

                if (!paymentSelect.value && paymentSelect.options.length) {
                    paymentSelect.selectedIndex = 0;
                }
            }

            if (bookingState.receivingMethod) {
                Array.prototype.forEach.call(receiveMethodInputs, function (input) {
                    input.checked = input.value === bookingState.receivingMethod;
                });
            }

            if (bookingState.returningMethod) {
                Array.prototype.forEach.call(returnMethodInputs, function (input) {
                    input.checked = input.value === bookingState.returningMethod;
                });
            }

            setTermsAccepted(hasAcceptedTerms, false);
        }

        function updateMethodOptionStyles() {
            if (!bookingCard) {
                return;
            }

            bookingCard.querySelectorAll(".cart-method-option").forEach(function (option) {
                var radio = option.querySelector("input[type='radio']");
                option.classList.toggle("is-selected", Boolean(radio && radio.checked));
            });
        }

        function getBookingSnapshot() {
            if (!bookingCard) {
                return {};
            }

            enforceReceiveScheduleConstraints(getActiveCartItems());

            var derivedSchedule = buildDerivedReturnSchedule();

            if (returnDateInput && derivedSchedule.date) {
                returnDateInput.value = derivedSchedule.date;
            }

            if (returnTimeSelect && derivedSchedule.time) {
                returnTimeSelect.value = derivedSchedule.time;
            }

            var receivingMethod = getMethodValue(receiveMethodInputs, "pickup");
            var returningMethod = getMethodValue(returnMethodInputs, "meetup");
            var servicesCartActive = isServicesCartViewActive();

            if (servicesCartActive) {
                receivingMethod = "meetup";
                returningMethod = "meetup";
            }

            var hasDelivery = !servicesCartActive && (receivingMethod === "delivery" || returningMethod === "delivery");
            var hasMeetup = servicesCartActive || receivingMethod === "meetup" || returningMethod === "meetup";
            var placeValue = "";

            if (hasMeetup) {
                if (servicesCartActive) {
                    placeValue = eventPlaceInput ? String(eventPlaceInput.value || "").trim() : "";
                } else if (placeSelect) {
                    placeValue = String(placeSelect.value || "").trim();
                }
            }

            return {
                receiveDate: receiveDateInput ? receiveDateInput.value : "",
                receiveTime: receiveTimeSelect ? receiveTimeSelect.value : "",
                place: placeValue,
                returnDate: derivedSchedule.date || (returnDateInput ? returnDateInput.value : ""),
                returnTime: derivedSchedule.time || (returnTimeSelect ? returnTimeSelect.value : ""),
                courier: hasDelivery && courierSelect ? courierSelect.value : "",
                receivingMethod: receivingMethod,
                returningMethod: returningMethod,
                paymentMethod: paymentSelect ? paymentSelect.value : "",
                termsAccepted: hasAcceptedTerms
            };
        }

        function saveBookingSnapshot() {
            bookingState = getBookingSnapshot();
            saveJsonStorage(bookingStorageKey, bookingState);
        }

        function getCartRentalDays(items) {
            var source = Array.isArray(items) ? items : getCartItems();
            var maxDays = 1;

            source.forEach(function (item) {
                if (!item || isUnavailableCartItem(item)) {
                    return;
                }

                var itemDays = Number.parseInt(item.days, 10);
                if (!Number.isFinite(itemDays) || itemDays < 1) {
                    itemDays = 1;
                }

                if (itemDays > maxDays) {
                    maxDays = itemDays;
                }
            });

            return maxDays;
        }

        function buildDerivedReturnSchedule(items) {
            var receiveDateValue = receiveDateInput ? String(receiveDateInput.value || "").trim() : "";
            var receiveTimeValue = receiveTimeSelect ? String(receiveTimeSelect.value || "").trim() : "";

            if (!receiveDateValue) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            var dateParts = receiveDateValue.split("-");
            if (dateParts.length !== 3) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            var year = Number.parseInt(dateParts[0], 10);
            var month = Number.parseInt(dateParts[1], 10);
            var day = Number.parseInt(dateParts[2], 10);

            if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            if (isServicesCartViewActive()) {
                var serviceDuration = getServiceDurationConfig();
                var serviceStartTimestamp = parseCartLocalScheduleTimestamp(receiveDateValue, receiveTimeValue);

                if (!Number.isFinite(serviceStartTimestamp)) {
                    return {
                        date: "",
                        time: receiveTimeValue
                    };
                }

                var durationMs = serviceDuration.unit === "days"
                    ? (serviceDuration.value * 24 * 60 * 60 * 1000)
                    : (serviceDuration.value * 60 * 60 * 1000);
                var serviceEndDate = new Date(serviceStartTimestamp + durationMs);

                if (Number.isNaN(serviceEndDate.getTime())) {
                    return {
                        date: "",
                        time: receiveTimeValue
                    };
                }

                return {
                    date: dateKeyFromDate(serviceEndDate),
                    time: String(serviceEndDate.getHours()).padStart(2, "0") + ":00"
                };
            }

            var rentalDays = getCartRentalDays(items);
            var returnDate = new Date(year, month - 1, day + rentalDays);

            if (Number.isNaN(returnDate.getTime())) {
                return {
                    date: "",
                    time: receiveTimeValue
                };
            }

            return {
                date: dateKeyFromDate(returnDate),
                time: receiveTimeValue
            };
        }

        function syncReturnDateTimeFromReceive(items) {
            if (!returnDateInput && !returnTimeSelect) {
                return;
            }

            enforceReceiveScheduleConstraints(items);

            var receiveDateValue = receiveDateInput ? String(receiveDateInput.value || "").trim() : "";
            var derivedSchedule = buildDerivedReturnSchedule(items);

            if (returnDateInput) {
                if (receiveDateValue) {
                    returnDateInput.min = receiveDateValue;
                }

                if (derivedSchedule.date) {
                    returnDateInput.value = derivedSchedule.date;
                }

                if (!returnDateInput.readOnly) {
                    returnDateInput.readOnly = true;
                }

                if (!returnDateInput.hasAttribute("readonly")) {
                    returnDateInput.setAttribute("readonly", "readonly");
                }

                if (!returnDateInput.disabled) {
                    returnDateInput.disabled = true;
                }

                if (returnDateInput.getAttribute("aria-disabled") !== "true") {
                    returnDateInput.setAttribute("aria-disabled", "true");
                }

                if (returnDateInput.tabIndex !== -1) {
                    returnDateInput.tabIndex = -1;
                }
            }

            if (returnTimeSelect) {
                if (derivedSchedule.time) {
                    returnTimeSelect.value = derivedSchedule.time;
                }

                if (!returnTimeSelect.disabled) {
                    returnTimeSelect.disabled = true;
                }

                if (returnTimeSelect.getAttribute("aria-disabled") !== "true") {
                    returnTimeSelect.setAttribute("aria-disabled", "true");
                }

                if (returnTimeSelect.tabIndex !== -1) {
                    returnTimeSelect.tabIndex = -1;
                }
            }
        }

        function updateDeliveryFields() {
            var servicesCartActive = isServicesCartViewActive();
            var receivingMethod = getMethodValue(receiveMethodInputs, "pickup");
            var returningMethod = getMethodValue(returnMethodInputs, "meetup");
            var hasDelivery = !servicesCartActive && (receivingMethod === "delivery" || returningMethod === "delivery");
            var isReceiveDelivery = !servicesCartActive && receivingMethod === "delivery";
            var hasMeetup = servicesCartActive || receivingMethod === "meetup" || returningMethod === "meetup";

            syncServiceTimeOptionSets();

            if (lateNote) {
                if (servicesCartActive) {
                    lateNote.textContent = "Event duration = " + formatServiceDurationLabel() + ".";
                } else {
                    lateNote.textContent = "Late returns = P50/hour";
                }
            }

            if (methodsRow) {
                methodsRow.hidden = servicesCartActive;
            }

            Array.prototype.forEach.call(receiveMethodInputs, function (input) {
                input.disabled = servicesCartActive;

                if (servicesCartActive) {
                    input.setAttribute("aria-disabled", "true");
                } else {
                    input.removeAttribute("aria-disabled");
                }
            });

            Array.prototype.forEach.call(returnMethodInputs, function (input) {
                input.disabled = servicesCartActive;

                if (servicesCartActive) {
                    input.setAttribute("aria-disabled", "true");
                } else {
                    input.removeAttribute("aria-disabled");
                }
            });

            if (deliveryOnlyBlock) {
                deliveryOnlyBlock.hidden = !hasDelivery;
            }

            if (courierSelect) {
                courierSelect.disabled = !hasDelivery;

                if (!hasDelivery) {
                    courierSelect.value = "";
                } else if (!courierSelect.value && courierSelect.options.length) {
                    courierSelect.selectedIndex = 0;
                }
            }

            if (courierRow) {
                courierRow.hidden = servicesCartActive;
            }

            if (paymentSelect) {
                if (servicesCartActive) {
                    paymentSelect.value = "cash-meetup";
                    paymentSelect.hidden = true;
                    paymentSelect.disabled = true;
                    paymentSelect.setAttribute("aria-disabled", "true");

                    Array.prototype.forEach.call(paymentSelect.options, function (option) {
                        option.disabled = false;
                        option.hidden = false;
                    });
                } else {
                    paymentSelect.hidden = false;
                    paymentSelect.disabled = false;
                    paymentSelect.removeAttribute("aria-disabled");

                    var firstEnabledPaymentOptionValue = "";

                    Array.prototype.forEach.call(paymentSelect.options, function (option) {
                        var optionToken = String(option.value || "").toLowerCase().trim();
                        var shouldDisableOption = isReceiveDelivery && optionToken !== "gcash";

                        option.disabled = shouldDisableOption;
                        option.hidden = shouldDisableOption;

                        if (!shouldDisableOption && firstEnabledPaymentOptionValue === "") {
                            firstEnabledPaymentOptionValue = String(option.value || "");
                        }
                    });

                    if (isReceiveDelivery) {
                        paymentSelect.value = "gcash";
                    }

                    var selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
                    var hasSelectableValue = selectedOption && !selectedOption.disabled;

                    if (!hasSelectableValue && firstEnabledPaymentOptionValue !== "") {
                        paymentSelect.value = firstEnabledPaymentOptionValue;
                    }
                }
            }

            if (bookingPaymentNote) {
                bookingPaymentNote.hidden = !isReceiveDelivery;
            }

            if (placeFieldLabel) {
                placeFieldLabel.textContent = servicesCartActive ? "Event Place:" : "Meeting Place:";
            }

            if (eventPlaceInput) {
                if (servicesCartActive) {
                    var fallbackPlaceValue = String(eventPlaceInput.value || "").trim();

                    if (!fallbackPlaceValue && placeSelect) {
                        fallbackPlaceValue = String(placeSelect.value || "").trim();
                    }

                    if (!fallbackPlaceValue) {
                        fallbackPlaceValue = String(bookingState.place || "").trim();
                    }

                    eventPlaceInput.value = fallbackPlaceValue;
                    eventPlaceInput.hidden = false;
                    eventPlaceInput.disabled = false;
                    eventPlaceInput.removeAttribute("aria-disabled");
                } else {
                    eventPlaceInput.hidden = true;
                    eventPlaceInput.disabled = true;
                    eventPlaceInput.setAttribute("aria-disabled", "true");
                }
            }

            if (placeSelect) {
                if (servicesCartActive) {
                    placeSelect.hidden = true;
                    placeSelect.disabled = true;
                    placeSelect.setAttribute("aria-hidden", "true");
                    placeSelect.setAttribute("aria-disabled", "true");
                } else {
                    placeSelect.hidden = false;
                    placeSelect.removeAttribute("aria-hidden");
                    placeSelect.disabled = !hasMeetup;

                    if (!hasMeetup) {
                        placeSelect.value = "";
                    } else if (!placeSelect.value && placeSelect.options.length) {
                        placeSelect.selectedIndex = 0;
                    }

                    if (placeSelect.disabled) {
                        placeSelect.setAttribute("aria-disabled", "true");
                    } else {
                        placeSelect.removeAttribute("aria-disabled");
                    }
                }
            }

            if (placeFieldRow) {
                placeFieldRow.classList.toggle("is-disabled", !hasMeetup);
            }

            updateMethodOptionStyles();
        }

        function renderCartItems() {
            var servicesCartActive = isServicesCartViewActive();
            var items = getActiveCartItems();
            var equipmentItems = getCartItems();

            panel.querySelectorAll(".cart-item-card").forEach(function (node) {
                node.remove();
            });

            if (emptyMessage) {
                emptyMessage.textContent = servicesCartActive
                    ? "No service package selected yet. Open a service package then tap Purchase."
                    : "Your cart is empty. Add event packages or camera rentals to continue.";
                emptyMessage.hidden = items.length > 0;
            }

            if (!items.length) {
                if (totalNode) {
                    totalNode.textContent = formatMoney(0);
                }

                if (breakdownNode) {
                    breakdownNode.textContent = "Subtotal P 0.00";
                }

                syncCartCountBadges(equipmentItems);
                return;
            }

            var didAdjustItemQuantities = false;

            items.forEach(function (item) {
                if (servicesCartActive) {
                    var serviceLineTotal = item.price;
                    var serviceCard = document.createElement("article");
                    serviceCard.className = "cart-item-card";
                    serviceCard.setAttribute("data-cart-item-id", item.id);
                    serviceCard.innerHTML = '' +
                        '<div class="cart-item-copy">' +
                            '<h2 class="cart-item-name">' + escapeHtml(String(item.name || "Service Package").toUpperCase()) + '</h2>' +
                            '<p class="cart-item-copy-text">' + escapeHtml(item.copy) + '</p>' +
                        '</div>' +
                        '<div class="cart-item-thumb">' +
                            '<img class="cart-item-thumb-image" src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name) + '">' +
                        '</div>' +
                        '<div class="cart-item-pricebox">' +
                            '<p class="cart-item-price-label">Duration:</p>' +
                            '<strong>' + escapeHtml(formatServiceDurationLabel(item)) + '</strong>' +
                            '<p class="cart-item-price-label">Price:</p>' +
                            '<strong>' + formatMoney(serviceLineTotal) + '</strong>' +
                        '</div>' +
                        '<button class="cart-remove-button" type="button" aria-label="Remove item" data-cart-remove>&#10005;</button>';

                    panel.appendChild(serviceCard);
                    return;
                }

                var itemIsUnavailable = isUnavailableCartItem(item);
                var itemProductKey = extractCameraProductKeyFromCartItem(item);
                var capacityParsed = Number.parseInt(equipmentAvailability.capacities[itemProductKey], 10);
                var quantityMax = Number.isFinite(capacityParsed)
                    ? Math.max(1, capacityParsed)
                    : 20;
                var stockCount = itemIsUnavailable
                    ? 0
                    : (Number.isFinite(capacityParsed)
                        ? Math.max(0, capacityParsed)
                        : quantityMax);

                if (!itemIsUnavailable && item.qty > quantityMax) {
                    item.qty = quantityMax;
                    didAdjustItemQuantities = true;
                }

                var lineTotal = itemIsUnavailable ? 0 : (item.price * item.qty * item.days);
                var nameLabel = String(item.name).toUpperCase();
                var card = document.createElement("article");
                card.className = "cart-item-card" + (itemIsUnavailable ? " is-unavailable" : "");
                card.setAttribute("data-cart-item-id", item.id);
                card.innerHTML = '' +
                    '<div class="cart-item-copy">' +
                        '<h2 class="cart-item-name' + (itemIsUnavailable ? ' is-unavailable' : '') + '">' + escapeHtml(nameLabel) + (itemIsUnavailable ? ' <span class="cart-item-stock-note">(OUT OF STOCK)</span>' : '') + '</h2>' +
                        '<p class="cart-item-copy-text">' + escapeHtml(item.copy) + '</p>' +
                        '<p class="cart-item-available-stock">(Stock: ' + escapeHtml(String(stockCount)) + ')</p>' +
                    '</div>' +
                    '<div class="cart-item-thumb' + (itemIsUnavailable ? ' cart-item-thumb-missing' : '') + '">' +
                        (itemIsUnavailable
                            ? '<span class="cart-item-thumb-missing-text">Missing</span>'
                            : '<img class="cart-item-thumb-image" src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name) + '">') +
                    '</div>' +
                    '<label class="cart-mini-field cart-item-days-field">' +
                        '<span>Days Reserved</span>' +
                        '<input type="number" min="1" max="14" value="' + item.days + '" data-cart-edit="days"' + (itemIsUnavailable ? ' disabled' : '') + '>' +
                    '</label>' +
                    '<div class="cart-item-pricebox">' +
                        '<label class="cart-mini-field">' +
                            '<span>Qty</span>' +
                            '<input type="number" min="1" max="' + quantityMax + '" value="' + item.qty + '" data-cart-edit="qty"' + (itemIsUnavailable ? ' disabled' : '') + '>' +
                        '</label>' +
                        '<p class="cart-item-price-label">' + (itemIsUnavailable ? 'Status:' : 'Price:') + '</p>' +
                        '<strong>' + (itemIsUnavailable ? 'Unavailable' : formatMoney(lineTotal)) + '</strong>' +
                    '</div>' +
                    '<button class="cart-remove-button" type="button" aria-label="Remove item" data-cart-remove>&#10005;</button>';

                panel.appendChild(card);
            });

            if (!servicesCartActive && didAdjustItemQuantities) {
                saveCartItems(items);
                enforceReceiveScheduleConstraints(items);
                syncReturnDateTimeFromReceive(items);
                saveBookingSnapshot();
                setBookingNoteLinkVisibility(false);
            }

            panel.querySelectorAll(".cart-item-thumb-image").forEach(function (imageNode) {
                imageNode.addEventListener("error", function () {
                    var thumbNode = imageNode.closest(".cart-item-thumb");
                    if (!thumbNode) {
                        return;
                    }

                    thumbNode.classList.add("cart-item-thumb-missing");
                    thumbNode.innerHTML = '<span class="cart-item-thumb-missing-text">Missing</span>';
                });
            });

            refreshTotals(items);
            syncCartCountBadges(equipmentItems);
        }

        function refreshTotals(items) {
            var servicesCartActive = isServicesCartViewActive();
            var activeItems = Array.isArray(items) ? items : getActiveCartItems();
            var subtotal = activeItems.reduce(function (sum, item) {
                if (isUnavailableCartItem(item)) {
                    return sum;
                }

                if (servicesCartActive) {
                    return sum + item.price;
                }

                return sum + (item.price * item.qty * item.days);
            }, 0);

            if (totalNode) {
                totalNode.textContent = formatMoney(subtotal);
            }

            if (breakdownNode) {
                breakdownNode.textContent = "Subtotal " + formatMoney(subtotal);
            }
        }

        function handleCartPanelInput(event) {
            if (isServicesCartViewActive()) {
                return;
            }

            var target = event.target;
            var field = target.getAttribute("data-cart-edit");
            if (!field) {
                return;
            }

            var card = target.closest("[data-cart-item-id]");
            if (!card) {
                return;
            }

            var itemId = card.getAttribute("data-cart-item-id");
            var nextValue = Number.parseInt(target.value, 10);
            var maxValue = Number.parseInt(target.getAttribute("max"), 10);

            if (!Number.isFinite(nextValue) || nextValue < 1) {
                nextValue = 1;
            }

            if (Number.isFinite(maxValue) && maxValue > 0 && nextValue > maxValue) {
                nextValue = maxValue;
            }

            target.value = String(nextValue);

            var items = getCartItems().map(function (item) {
                if (item.id === itemId) {
                    item[field] = nextValue;
                }

                return item;
            });

            saveCartItems(items);

            enforceReceiveScheduleConstraints(items);
            syncReturnDateTimeFromReceive(items);
            saveBookingSnapshot();

            renderCartItems();
        }

        panel.addEventListener("click", function (event) {
            var removeButton = event.target.closest("[data-cart-remove]");
            if (!removeButton) {
                return;
            }

            if (isServicesCartViewActive()) {
                clearServiceBookingItem();
                saveBookingSnapshot();
                renderCartItems();
                return;
            }

            var card = removeButton.closest("[data-cart-item-id]");
            if (!card) {
                return;
            }

            var itemId = card.getAttribute("data-cart-item-id");
            var filteredItems = getCartItems().filter(function (item) {
                return item.id !== itemId;
            });

            saveCartItems(filteredItems);
            enforceReceiveScheduleConstraints(filteredItems);
            syncReturnDateTimeFromReceive(filteredItems);
            saveBookingSnapshot();
            renderCartItems();
        });

        panel.addEventListener("input", handleCartPanelInput);
        panel.addEventListener("change", handleCartPanelInput);

        function shiftReceiveDateCalendarMonth(monthOffset) {
            if (!receiveDateCalendarGrid) {
                return;
            }

            if (!(receiveDateCalendarCursor instanceof Date) || Number.isNaN(receiveDateCalendarCursor.getTime())) {
                var context = getCartBookingContext();
                var seedDate = dateFromCartDateKey(receiveDateInput ? receiveDateInput.value : "")
                    || dateFromCartDateKey(context.minimumDateKey)
                    || new Date();
                receiveDateCalendarCursor = new Date(seedDate.getFullYear(), seedDate.getMonth(), 1);
            }

            receiveDateCalendarCursor = new Date(
                receiveDateCalendarCursor.getFullYear(),
                receiveDateCalendarCursor.getMonth() + monthOffset,
                1
            );

            clampReceiveDateCalendarCursor(getCartBookingContext());
            renderReceiveDateCalendar(getActiveCartItems());
        }

        if (receiveDateCalendarGrid) {
            receiveDateCalendarGrid.addEventListener("click", function (event) {
                var dateButton = event.target.closest("[data-receive-calendar-date]");

                if (!dateButton || dateButton.disabled || !receiveDateInput) {
                    return;
                }

                var selectedDate = String(dateButton.getAttribute("data-receive-calendar-date") || "").trim();

                if (!selectedDate) {
                    return;
                }

                var selectedDateParts = parseCartDateKey(selectedDate);
                if (!selectedDateParts) {
                    return;
                }

                receiveDateInput.value = selectedDate;
                receiveDateCalendarCursor = new Date(selectedDateParts.year, selectedDateParts.month - 1, 1);

                var currentItems = getActiveCartItems();
                enforceReceiveScheduleConstraints(currentItems);
                syncReturnDateTimeFromReceive(currentItems);
                saveBookingSnapshot();
                refreshTotals();
            });
        }

        receiveDateCalendarNavButtons.forEach(function (navButton) {
            navButton.addEventListener("click", function () {
                var direction = String(navButton.getAttribute("data-receive-calendar-nav") || "").trim().toLowerCase();

                if (direction === "prev") {
                    shiftReceiveDateCalendarMonth(-1);
                    return;
                }

                if (direction === "next") {
                    shiftReceiveDateCalendarMonth(1);
                }
            });
        });

        [returnDateInput, returnTimeSelect].forEach(function (lockedControl) {
            if (!lockedControl) {
                return;
            }

            lockedControl.addEventListener("input", function () {
                syncReturnDateTimeFromReceive();
                saveBookingSnapshot();
            });

            lockedControl.addEventListener("change", function () {
                syncReturnDateTimeFromReceive();
                saveBookingSnapshot();
            });
        });

        if (bookingCard) {
            bookingCard.querySelectorAll("[data-booking-field], input[name='receivingMethod'], input[name='returningMethod']").forEach(function (control) {
                control.addEventListener("change", function () {
                    if (control === receiveDateInput || control === receiveTimeSelect) {
                        var currentItems = getActiveCartItems();
                        enforceReceiveScheduleConstraints(currentItems);
                        syncReturnDateTimeFromReceive(currentItems);
                    }

                    updateDeliveryFields();
                    saveBookingSnapshot();
                    refreshTotals();
                });
            });
        }

        uploadInputs.forEach(function (input) {
            input.addEventListener("change", function () {
                var label = input.getAttribute("data-booking-field") === "validIdImage"
                    ? bookingCard.querySelector("[data-upload-label='validId']")
                    : bookingCard.querySelector("[data-upload-label='selfieId']");

                if (!label) {
                    return;
                }

                if (input.files && input.files.length) {
                    label.textContent = input.files[0].name;
                    return;
                }

                label.textContent = "No file selected";
            });
        });

        if (paymentSelect && bookingNote) {
            paymentSelect.addEventListener("change", function () {
                if (paymentSelect.value === "gcash") {
                    bookingNote.textContent = "GCash selected. After confirming, review the QR details before finalizing your reservation.";
                    return;
                }

                bookingNote.textContent = "Demo flow only: no real reservation or payment will be processed.";
            });
        }

        if (historyList) {
            historyList.addEventListener("click", function (event) {
                var viewStatusButton = event.target.closest("[data-cart-history-view-status]");
                if (viewStatusButton) {
                    orderStatusFocusId = String(viewStatusButton.getAttribute("data-cart-order-id") || "").trim();
                    activeOrderStatusEntryId = orderStatusFocusId;
                    setCartView("order-status");
                    return;
                }

                var reReserveButton = event.target.closest("[data-cart-history-rereserve]");
                if (!reReserveButton) {
                    return;
                }

                var targetOrderId = String(reReserveButton.getAttribute("data-cart-order-id") || "").trim();
                if (!targetOrderId) {
                    return;
                }

                handleHistoryReReserve(targetOrderId);
            });
        }

        if (termsCheckbox) {
            termsCheckbox.addEventListener("click", function (event) {
                event.preventDefault();

                if (hasAcceptedTerms) {
                    setTermsAccepted(false);

                    if (bookingNote) {
                        bookingNote.textContent = "Terms agreement removed. Review the Terms and Conditions again before confirming.";
                    }
                    return;
                }

                openTermsModal();
            });
        }

        if (termsOpenButton) {
            termsOpenButton.addEventListener("click", function () {
                openTermsModal();
            });
        }

        if (termsModalScroll) {
            termsModalScroll.addEventListener("scroll", function () {
                updateTermsAgreeAvailability();
            });
        }

        termsModalCloseButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                closeTermsModal();
            });
        });

        if (termsModalAgreeButton) {
            termsModalAgreeButton.addEventListener("click", function () {
                if (!termsModalScrolledToEnd) {
                    return;
                }

                setTermsAccepted(true);
                closeTermsModal();

                if (bookingNote) {
                    bookingNote.textContent = "Terms accepted. Review your reservation details, then confirm when ready.";
                }
            });
        }

        if (confirmButton && bookingNote) {
            confirmButton.addEventListener("click", function () {
                var items = getActiveCartItems();
                var servicesCartActive = isServicesCartViewActive();

                enforceReceiveScheduleConstraints(items);
                syncReturnDateTimeFromReceive(items);
                saveBookingSnapshot();

                if (!items.length) {
                    bookingNote.textContent = servicesCartActive
                        ? "Select a service package first by tapping Purchase from a package page."
                        : "Add at least one item before confirming your demo reservation.";
                    return;
                }

                if (isCustomerLoggedIn && !hasCustomerContactNumber()) {
                    bookingNote.textContent = customerAccountSettingsPath
                        ? "Please add your contact number in Account Settings before confirming your reservation."
                        : "Please add your contact number before confirming your reservation.";
                    setBookingNoteLinkVisibility(customerAccountSettingsPath !== "");
                    return;
                }

                if (servicesCartActive) {
                    var eventPlaceValue = eventPlaceInput ? String(eventPlaceInput.value || "").trim() : "";

                    if (!eventPlaceValue) {
                        bookingNote.textContent = "Please enter the Event Place before confirming.";
                        return;
                    }
                }

                if (!hasValidCurrentReceiveSchedule(items)) {
                    bookingNote.textContent = servicesCartActive
                        ? "Please choose a valid event start schedule."
                        : "No available receiving slots for the selected quantities and rental days. Adjust your cart or choose a later schedule.";
                    return;
                }

                var unavailableItems = getUnavailableCartItems(items);

                if (!servicesCartActive && unavailableItems.length) {
                    openUnavailableModal(unavailableItems, function () {
                        var remainingItems = removeUnavailableCartItems();
                        enforceReceiveScheduleConstraints(remainingItems);
                        syncReturnDateTimeFromReceive(remainingItems);
                        saveBookingSnapshot();
                        renderCartItems();

                        if (!remainingItems.length) {
                            bookingNote.textContent = "Out of stock items were removed from your reservation.";
                            showCartToast("Unavailable items removed");
                            return;
                        }

                        bookingNote.textContent = "Out of stock items were removed. Reservation request staged in demo mode.";
                        showCartToast("Unavailable items removed");
                    });

                    return;
                }

                if (isSubmittingPendingOrder) {
                    return;
                }

                if (!hasAcceptedTerms) {
                    bookingNote.textContent = "You must scroll through and agree to the Terms and Conditions before confirming.";
                    openTermsModal();
                    return;
                }

                syncConfirmButtonAvailability();

                createPendingOrderRecord(items)
                    .then(function (pendingOrder) {
                        var paymentMethod = String(pendingOrder.paymentMethod || "").toLowerCase().trim();

                        if (paymentMethod === "gcash") {
                            bookingNote.textContent = "Review the GCash payment details, then tap Continue Reservation.";
                            openGcashModal({
                                mode: "confirm-booking",
                                orderRecord: pendingOrder
                            });
                            return;
                        }

                        handlePendingOrderSubmission(pendingOrder);
                    })
                    .catch(function (error) {
                        if (bookingNote) {
                            bookingNote.textContent = error && error.message
                                ? String(error.message)
                                : "Unable to create your reservation. Please check your selected items and try again.";
                        }
                    })
                    .finally(function () {
                        if (!isSubmittingPendingOrder) {
                            syncConfirmButtonAvailability();
                        }
                    });
            });
        }

        renderOrderStatusList();
        if (window.CreatyTransactionHistory) {
            window.CreatyTransactionHistory.setRenderer("customer", renderHistoryList);
        }
        renderHistoryList();
        restoreBookingDefaults();
        enforceReceiveScheduleConstraints(getActiveCartItems());
        syncReturnDateTimeFromReceive(getActiveCartItems());
        updateDeliveryFields();
        saveBookingSnapshot();
        renderCartItems();
        syncConfirmButtonAvailability();
        scheduleCartTopOfHourReload();
        initializeCustomerLiveUpdates();

        window.addEventListener("resize", function () {
            if (activeCartView === "order-status") {
                syncOrderStatusColumnWidths();
            }
        });
    }

    initializeUniversalTopbarSearch();
    initializeCustomerMessageModal();
    initializeAdminLiveNotifications();
    initializeAdminNotificationsPage();
    initializeAdminBusinessReportPrint();
    syncCartCountBadges();
    initializeAddToCartButtons();
    initializeServicePurchaseButtons();
    initializeCustomerNotificationCenter();
    initializeCartPage();

    // Calendar Initialization
    var calendarCard = document.querySelector(".product-calendar-card");
    if (calendarCard) {
        var monthSelect = document.getElementById("calendar-month-select");
        var yearSelect = document.getElementById("calendar-year-select");
        var gridContainer = document.getElementById("calendar-grid-container");
        var calendarDays = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
        var calendarProductKey = (calendarCard.getAttribute("data-product-key") || "").toLowerCase();
        var calendarAvailabilityPayload = window.__creatyEquipmentAvailability && typeof window.__creatyEquipmentAvailability === "object"
            ? window.__creatyEquipmentAvailability
            : {};
        var calendarBookingSource = calendarAvailabilityPayload.booking && typeof calendarAvailabilityPayload.booking === "object"
            ? calendarAvailabilityPayload.booking
            : {};
        var calendarProductsSource = calendarAvailabilityPayload.products && typeof calendarAvailabilityPayload.products === "object"
            ? calendarAvailabilityPayload.products
            : {};
        var calendarReservationsSource = Array.isArray(calendarAvailabilityPayload.reservations)
            ? calendarAvailabilityPayload.reservations
            : [];
        var calendarOpenHourParsed = Number.parseInt(calendarBookingSource.openHour, 10);
        var calendarCloseHourParsed = Number.parseInt(calendarBookingSource.closeHour, 10);
        var calendarCutoffHourParsed = Number.parseInt(calendarBookingSource.sameDayCutoffHour, 10);
        var calendarLeadHoursParsed = Number.parseInt(calendarBookingSource.leadHours, 10);
        var calendarOpenHour = Number.isFinite(calendarOpenHourParsed) ? Math.max(0, Math.min(23, calendarOpenHourParsed)) : 8;
        var calendarCloseHour = Number.isFinite(calendarCloseHourParsed) ? Math.max(0, Math.min(23, calendarCloseHourParsed)) : 17;
        var calendarSameDayCutoffHour = Number.isFinite(calendarCutoffHourParsed) ? Math.max(0, Math.min(23, calendarCutoffHourParsed)) : 15;
        var calendarLeadHours = Number.isFinite(calendarLeadHoursParsed) && calendarLeadHoursParsed >= 0 ? calendarLeadHoursParsed : 2;
        var calendarProductCapacity = 1;
        var calendarReservations = [];

        if (!calendarProductKey) {
            calendarProductKey = extractProductKeyFromHref(window.location.search || "");
        }

        if (calendarCloseHour < calendarOpenHour) {
            calendarCloseHour = calendarOpenHour;
        }

        if (calendarProductKey && calendarProductsSource[calendarProductKey]) {
            var productCapacityParsed = Number.parseInt(calendarProductsSource[calendarProductKey].capacity, 10);

            if (Number.isFinite(productCapacityParsed)) {
                calendarProductCapacity = Math.max(0, productCapacityParsed);
            }
        }

        function parseCalendarSlotHour(timeValue) {
            var normalized = String(timeValue || "").trim();
            var matches = normalized.match(/^(\d{2}):(\d{2})$/);

            if (!matches) {
                return null;
            }

            var hour = Number.parseInt(matches[1], 10);
            var minute = Number.parseInt(matches[2], 10);

            if (!Number.isFinite(hour) || !Number.isFinite(minute) || minute !== 0) {
                return null;
            }

            return hour;
        }

        function parseCalendarDateKey(dateKey) {
            var normalized = String(dateKey || "").trim();
            var matches = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);

            if (!matches) {
                return null;
            }

            var year = Number.parseInt(matches[1], 10);
            var month = Number.parseInt(matches[2], 10);
            var day = Number.parseInt(matches[3], 10);
            var parsedDate = new Date(year, month - 1, day);

            if (
                Number.isNaN(parsedDate.getTime())
                || parsedDate.getFullYear() !== year
                || (parsedDate.getMonth() + 1) !== month
                || parsedDate.getDate() !== day
            ) {
                return null;
            }

            return {
                year: year,
                month: month,
                day: day
            };
        }

        function parseCalendarScheduleTimestamp(dateKey, timeValue) {
            var dateParts = parseCalendarDateKey(dateKey);
            var hour = parseCalendarSlotHour(timeValue);

            if (!dateParts || !Number.isFinite(hour)) {
                return Number.NaN;
            }

            var scheduleDate = new Date(dateParts.year, dateParts.month - 1, dateParts.day, hour, 0, 0, 0);

            if (
                Number.isNaN(scheduleDate.getTime())
                || scheduleDate.getFullYear() !== dateParts.year
                || (scheduleDate.getMonth() + 1) !== dateParts.month
                || scheduleDate.getDate() !== dateParts.day
                || scheduleDate.getHours() !== hour
            ) {
                return Number.NaN;
            }

            return scheduleDate.getTime();
        }

        if (calendarProductKey) {
            calendarReservationsSource.forEach(function (reservation) {
                if (!reservation || typeof reservation !== "object") {
                    return;
                }

                var reservationProductKey = String(reservation.productKey || "").trim().toLowerCase();

                if (!reservationProductKey || reservationProductKey !== calendarProductKey) {
                    return;
                }

                var qtyParsed = Number.parseInt(reservation.qty, 10);
                var daysParsed = Number.parseInt(reservation.days, 10);
                var qty = Number.isFinite(qtyParsed) && qtyParsed > 0 ? qtyParsed : 1;
                var days = Number.isFinite(daysParsed) && daysParsed > 0 ? daysParsed : 1;
                var startDate = String(reservation.startDate || "").trim();
                var startTime = String(reservation.startTime || "").trim();
                var startTs = parseCalendarScheduleTimestamp(startDate, startTime);

                if (!Number.isFinite(startTs)) {
                    return;
                }

                calendarReservations.push({
                    qty: qty,
                    startTs: startTs,
                    endTs: startTs + (days * 24 * 60 * 60 * 1000)
                });
            });
        }

        function setStartOfDay(dateValue) {
            var date = new Date(dateValue.getFullYear(), dateValue.getMonth(), dateValue.getDate());
            date.setHours(0, 0, 0, 0);
            return date;
        }

        function getCalendarToday() {
            return setStartOfDay(new Date());
        }

        function getCalendarBookingContext() {
            var now = new Date();
            var today = getCalendarToday();
            var currentHour = now.getHours();
            var minimumDate = new Date(
                today.getFullYear(),
                today.getMonth(),
                today.getDate() + (currentHour >= calendarSameDayCutoffHour ? 1 : 0)
            );

            return {
                todayKey: dateKeyFromDate(today),
                minimumDateKey: dateKeyFromDate(minimumDate),
                sameDayMinimumHour: Math.max(calendarOpenHour, currentHour + calendarLeadHours)
            };
        }

        function isCalendarSlotAvailable(dateKey, slotHour, bookingContext) {
            if (!Number.isFinite(slotHour)) {
                return false;
            }

            if (slotHour < calendarOpenHour || slotHour > calendarCloseHour) {
                return false;
            }

            if (!dateKey || dateKey < bookingContext.minimumDateKey) {
                return false;
            }

            if (dateKey === bookingContext.todayKey && slotHour < bookingContext.sameDayMinimumHour) {
                return false;
            }

            if (calendarProductCapacity < 1) {
                return false;
            }

            var slotTimeValue = padTwo(slotHour) + ":00";
            var startTs = parseCalendarScheduleTimestamp(dateKey, slotTimeValue);

            if (!Number.isFinite(startTs)) {
                return false;
            }

            var endTs = startTs + (24 * 60 * 60 * 1000);
            var occupiedQty = peakReservedQuantity(calendarReservations, startTs, endTs);

            return (occupiedQty + 1) <= calendarProductCapacity;
        }

        function isCalendarDateAvailable(dateKey) {
            var bookingContext = getCalendarBookingContext();

            for (var slotHour = calendarOpenHour; slotHour <= calendarCloseHour; slotHour += 1) {
                if (isCalendarSlotAvailable(dateKey, slotHour, bookingContext)) {
                    return true;
                }
            }

            return false;
        }

        function rebuildYearOptions() {
            if (!yearSelect) {
                return;
            }

            yearSelect.innerHTML = "";

            var startYear = getCalendarToday().getFullYear();
            var endYear = startYear + 2;

            for (var yearValue = startYear; yearValue <= endYear; yearValue += 1) {
                var option = document.createElement("option");
                option.value = String(yearValue);
                option.textContent = String(yearValue);
                yearSelect.appendChild(option);
            }
        }

        function updateDisables() {
            var today = getCalendarToday();
            var currentY = today.getFullYear();
            var currentM = today.getMonth();

            Array.from(yearSelect.options).forEach(function (opt) {
                var y = Number.parseInt(opt.value, 10);
                opt.disabled = y < currentY;
            });

            if (yearSelect.options[yearSelect.selectedIndex] && yearSelect.options[yearSelect.selectedIndex].disabled) {
                var firstAbleYear = Array.from(yearSelect.options).find(function (o) {
                    return !o.disabled;
                });

                if (firstAbleYear) {
                    yearSelect.value = firstAbleYear.value;
                }
            }

            var selectedYear = Number.parseInt(yearSelect.value, 10);

            Array.from(monthSelect.options).forEach(function (opt) {
                var monthIndex = Number.parseInt(opt.value, 10);
                var isPastMonth = selectedYear < currentY || (selectedYear === currentY && monthIndex < currentM);
                opt.disabled = isPastMonth;
            });

            if (monthSelect.options[monthSelect.selectedIndex] && monthSelect.options[monthSelect.selectedIndex].disabled) {
                var firstAvailableMonth = Array.from(monthSelect.options).find(function (o) {
                    return !o.disabled;
                });

                if (firstAvailableMonth) {
                    monthSelect.value = firstAvailableMonth.value;
                }
            }
        }

        function renderCalendar() {
            var selectedMonth = parseInt(monthSelect.value, 10);
            var selectedYear = parseInt(yearSelect.value, 10);

            var firstDay = new Date(selectedYear, selectedMonth, 1).getDay();
            var daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
            var daysInPrevMonth = new Date(selectedYear, selectedMonth, 0).getDate();
            var effectiveToday = getCalendarToday();

            var html = "";
            
            calendarDays.forEach(function(day) {
                html += '<span class="calendar-day-name">' + day + '</span>';
            });
            
            var date = 1;
            var nextDate = 1;
            var needsSixthRow = (firstDay + daysInMonth > 35);
            var totalRows = needsSixthRow ? 6 : 5;
            
            for (var row = 0; row < totalRows; row++) {
                for (var d = 0; d < 7; d++) {
                    if (row === 0 && d < firstDay) {
                        var prevDay = daysInPrevMonth - firstDay + d + 1;
                        html += '<span class="calendar-date is-muted">' + prevDay + '</span>';
                    } else if (date > daysInMonth) {
                        html += '<span class="calendar-date is-muted">' + nextDate + '</span>';
                        nextDate++;
                    } else {
                        var currentDate = new Date(selectedYear, selectedMonth, date);
                        var dateKey = dateKeyFromDate(currentDate);
                        var isPastDay = currentDate < effectiveToday;
                        var isAvailableDay = !isPastDay && isCalendarDateAvailable(dateKey);
                        var classes = "calendar-date";

                        if (isPastDay) {
                            classes += " is-past is-unavailable";
                        } else if (isAvailableDay) {
                            classes += " is-available";
                        } else {
                            classes += " is-unavailable";
                        }
                        
                        html += '<span class="' + classes + '">' + date + '</span>';
                        date++;
                    }
                }
            }
            gridContainer.innerHTML = html;
        }

        if (monthSelect && yearSelect && gridContainer) {
            rebuildYearOptions();

            var startupToday = getCalendarToday();
            yearSelect.value = String(startupToday.getFullYear());
            monthSelect.value = String(startupToday.getMonth());

            monthSelect.addEventListener("change", renderCalendar);
            yearSelect.addEventListener("change", function() {
                updateDisables();
                renderCalendar();
            });
            updateDisables();
            renderCalendar();
        }
    }
});
