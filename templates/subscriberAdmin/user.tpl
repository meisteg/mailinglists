{FORM}
<!-- BEGIN LISTNAME -->
<b>{LISTNAME}</b><br /><br />
<!-- END LISTNAME -->
<!-- BEGIN USERADMIN -->
{MEMBER_LABEL}:<br />
{USER_HELP}{USER}{ADD_BUTTON}{REMOVE_BUTTON}<br />
<!-- END USERADMIN -->
<!-- BEGIN ANONADMIN -->
{ANON_LABEL}:<br />
{ANON_HELP}{ANON_EMAIL}{ANON_ADD}{ANON_REMOVE}
<!-- END ANONADMIN -->
{ENDFORM}
<!-- BEGIN LISTINGINFO -->
<br /><b>{LISTINGTITLE} {SECTIONINFO}</b><br /><br />
<!-- END LISTINGINFO -->
<!-- BEGIN LISTING -->
<table border="0" width="100%" cellspacing="1" cellpadding="3">
<tr class="bg_medium">
<td><b>{USER_LABEL}</b></td>
<td><b>{EMAIL_LABEL}</b></td>
<td align="center"><b>{DATE_LABEL}</b></td>
<td align="center"><b>{ACTIVE_LABEL}</b></td>
<td align="center"><b>{ACTION_LABEL}</b></td>
</tr>
{LISTING}
</table>
<!-- END LISTING -->
{NONE}
<!-- BEGIN SECTIONINFO -->
<br />
<div align="center">{SECTIONLINKS}<br />
{LIMITLINKS}</div>
<!-- END SECTIONINFO -->
