{START_FORM}
<div class="top-label">
    <!-- BEGIN email-options -->
    <div class="padded">{OPTIONS}</div>
    <!-- END email-options -->

    <div class="padded">
        {SUBJECT_LABEL}<br />{SUBJECT}
        <!-- BEGIN subject-error --><div class="error">{SUBJECT_ERROR}</div><!-- END subject-error -->
    </div>

    <fieldset><legend><strong>{HTML_EMAIL_LEGEND}</strong></legend>
        <div class="padded">{MSG_HTML}</div>
        <div class="padded">{FILE_MANAGER}</div>
    </fieldset>

    <!-- BEGIN body-error -->
    <div class="padded"><div class="error">{BODY_ERROR}</div></div>
    <!-- END body-error -->

    <fieldset><legend><strong>{TEXT_EMAIL_LEGEND}</strong></legend>
        <div class="padded">{MSG_TEXT}</div>
    </fieldset>

    <div class="padded">{SUBMIT}</div>
</div>
{END_FORM}
