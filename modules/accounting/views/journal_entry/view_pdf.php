<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<html>
<head>
<meta charset="utf-8">
<?php $this->load->view('report/convert_style'); ?>
<style>
  body { font-family: DejaVu Serif, sans-serif; font-size: 12px; }
  .num { text-align: right; }
  img { max-width: 100%; }
</style>
</head>
<body>
<?php $this->load->view('journal_entry/view_body', array_merge($this->load->get_vars(), ['for_pdf' => true])); ?>
</body>
</html>
