<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<html>
<head>
<meta charset="utf-8">
<?php $this->load->view('report/convert_style'); ?>
<style>
  body, table, td, th, h3, h4, h5 { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
  h3 { font-size: 24px; } h4 { font-size: 18px; }
  .num { text-align: right; }
  img { max-width: 100%; }
</style>
</head>
<body>
<?php $this->load->view('journal_entry/view_body', array_merge($this->load->get_vars(), ['for_pdf' => true])); ?>
</body>
</html>
