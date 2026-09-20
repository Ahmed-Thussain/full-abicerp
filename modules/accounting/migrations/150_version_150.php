<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_150 extends App_module_migration
{
    public function up()
    {
        // Journal entry attachments use the core `files` table
        // (rel_type = 'acc_journal_entry'), which needs no schema change.
    }
}
