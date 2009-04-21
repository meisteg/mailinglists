<h1>{MODULE_NAME}</h1>

<table cellpadding="4" cellspacing="1" width="100%">
  <tr>
    <th>{TITLE}</th>
    <th>{CREATED}</th>
    <th>{ACTION}</th>
  </tr>
<!-- BEGIN listrows -->
  <tr{TOGGLE}>
    <td>{TITLE}</td>
    <td>{CREATED}</td>
    <td>{ACTION}</td>
  </tr>
<!-- END listrows -->
<!-- BEGIN empty_message -->
  <tr{TOGGLE}>
    <td colspan="3">{EMPTY_MESSAGE}</td>
  </tr>
<!-- END empty_message -->
</table>
