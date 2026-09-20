<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #999; padding: 4px; text-align: left; }
  .num { text-align: right; }
  img { max-width: 100%; }
</style>
</head>
<body>
<?php $this->load->view('journal_entry/view_body', array_merge($this->load->get_vars(), ['for_pdf' => true])); ?>
</body>
</html>
