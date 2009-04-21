{START_FORM}
<div class="top-label">
    <div class="padded">
        {NAME_LABEL}<br />{NAME}
        <!-- BEGIN name-error --><div class="error">{NAME_ERROR}</div><!-- END name-error -->
    </div>
    <div class="padded">{DESCRIPTION_LABEL}<br />{DESCRIPTION}</div>
    <div class="padded">
        {FROM_NAME_LABEL}<br />{FROM_NAME}
        <!-- BEGIN from-name-error --><div class="error">{FROM_NAME_ERROR}</div><!-- END from-name-error -->
    </div>
    <div class="padded">
        {FROM_EMAIL_LABEL}<br />{FROM_EMAIL}
        <!-- BEGIN from-email-error --><div class="error">{FROM_EMAIL_ERROR}</div><!-- END from-email-error -->
    </div>
    <div class="padded">
        {ARCHIVE_LINK} {ARCHIVE_LINK_LABEL}<br />
        {DOUBLE_OPT_IN} {DOUBLE_OPT_IN_LABEL}<br />
        {S_EMAIL} {S_EMAIL_LABEL}<br />
        {U_EMAIL} {U_EMAIL_LABEL}
    </div>
    <!-- BEGIN subject-prefix -->
    <div class="padded">{SUBJECT_PREFIX_LABEL}<br />{SUBJECT_PREFIX}</div>
    <!-- END subject-prefix -->
    <div class="padded">{OPT_IN_MSG_LABEL}<br />{OPT_IN_MSG}</div>
    <div class="padded">{SUBSCRIBE_MSG_LABEL}<br />{SUBSCRIBE_MSG}</div>
    <div class="padded">{UNSUBSCRIBE_MSG_LABEL}<br />{UNSUBSCRIBE_MSG}</div>
    <div class="padded">{SUBMIT}</div>
</div>
{END_FORM}
