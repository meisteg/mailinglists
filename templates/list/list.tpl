<table cellpadding="4" cellspacing="1" width="100%">
  <tr>
    <th>{NAME} {NAME_SORT}</th>
    <th>{SUBSCRIBERS}</th>
    <th>{CREATED} {CREATED_SORT}</th>
    <th>{LAST_SENT}</th>
    <th>{ACTIVE} {ACTIVE_SORT}</th>
    <th>{ACTION}</th>
  </tr>
<!-- BEGIN listrows -->
  <tr{TOGGLE}>
    <td>{NAME}</td>
    <td>{SUBSCRIBERS}</td>
    <td>{CREATED}</td>
    <td>{LAST_SENT}</td>
    <td>{ACTIVE}</td>
    <td>{ACTION}</td>
  </tr>
<!-- END listrows -->
<!-- BEGIN empty_message -->
  <tr{TOGGLE}>
    <td colspan="6">{EMPTY_MESSAGE}</td>
  </tr>
<!-- END empty_message -->
</table>

<!-- BEGIN navigation -->
<div class="align-center">
{TOTAL_ROWS}<br />
{PAGE_LABEL} {PAGES}<br />
{LIMIT_LABEL} {LIMITS}
</div>
<!-- END navigation -->
<!-- BEGIN search -->
<div class="align-right">
{SEARCH}
</div>
<!-- END search -->
