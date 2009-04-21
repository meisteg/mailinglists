<table cellpadding="4" cellspacing="1" width="100%">
  <tr>
    <th>{USER}</th>
    <th>{SUBSCRIBED} {SUBSCRIBED_SORT}</th>
    <th>{ACTIVE} {ACTIVE_SORT}</th>
    <th>{HTML} {HTML_SORT}</th>
    <th>{ACTION}</th>
  </tr>
<!-- BEGIN listrows -->
  <tr{TOGGLE}>
    <td>{USER}</td>
    <td>{SUBSCRIBED}</td>
    <td>{ACTIVE}</td>
    <td>{HTML}</td>
    <td>{ACTION}</td>
  </tr>
<!-- END listrows -->
<!-- BEGIN empty_message -->
  <tr{TOGGLE}>
    <td colspan="5">{EMPTY_MESSAGE}</td>
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

<hr />

{START_FORM}
<div class="top-label">
    <div class="padded">{SUBSCRIBERS}<br />{SUBSCRIBE} {UNSUBSCRIBE}<br /><br />{ADD_ALL}</div>
</div>
{END_FORM}