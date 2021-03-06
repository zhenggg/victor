<?php

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class DelCard extends AbstractTool
{
    public $ajax_url;
    public function __construct()
    {
        $this->ajax_url = 'del_card';
    }

    protected function script()
    {
        $path = Request::path();
        $token = csrf_token();

        return <<<EOT

$('.grid-tools-{$this->ajax_url}').on('click', function() {

    if(confirm("确定要清除所有人员的卡号信息？")) {
        $.ajax({
            method: 'post',
            url: '{$this->ajax_url}',
            data: {
               '_method': 'PATCH',
               _token:'{$token}'
            },
            success: function () {
                $.pjax({container:'#pjax-container', url: '/{$path}' });
                toastr.success('操作成功');
            }
        });
    }
});

EOT;
    }
    public function render()
    {
        Admin::script($this->script());
        return view('admin.tools.button',
            [
                'button_name'=>'一键清除卡号',
                'ajax_url'=> $this->ajax_url
            ]
        );
    }
}