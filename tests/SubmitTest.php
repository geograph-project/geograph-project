<?php

use PHPUnit\Framework\TestCase;

require_once 'public_html/geograph/global.inc.php';
require_once 'libs/geograph/submissionhelper.class.php';

class SubmitTest extends TestCase
{
    public function testHandleFileUpload()
    {
        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/php' . mt_rand(),
            'error' => UPLOAD_ERR_OK,
            'size' => 123
        ];

        $smarty = SubmissionHelper::handleFileUpload($file);
        $this->assertNull($smarty->get_template_vars('error'));
    }

    public function testNormaliseGridReference()
    {
        $gridRef = '53.3498, -6.2603';
        $newGridRef = SubmissionHelper::normaliseGridReference($gridRef);
        $this->assertEquals('O15334980', $newGridRef);
    }
}
