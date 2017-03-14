<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Tools\BatchEnter;
use App\Admin\Extensions\Tools\DelCard;
use App\Admin\Extensions\Tools\BatchSend;
use App\Http\Controllers\Controller;


use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Form;
use Encore\Admin\Grid;

use Encore\Admin\Controllers\ModelForm;

use Illuminate\Http\Request;
use App\Vuser;
use App\Vcat;
use App\Salesman;
use App\Manager;
use App\Province;
use App\Hotel;
use App\Post;
use Illuminate\Support\Facades\DB;

use App\Admin\Extensions\CustomExporter;

class VusersController extends Controller
{
    use ModelForm;

    public function index()
    {
        return Admin::content(function (Content $content) {
            $content->header('人员');
            $content->description('列表');
            $content->body($this->grid()->render());
        });
    }

    private function grid()
    {
        return Vuser::grid(function (Grid $grid) {
            $grid->model()->orderBy('number','asc');
            $grid->number('参会人员编号');
            $grid->card('卡片号码')->editable();
            $grid->column('type','类别')->display(function(){
                $prrent_id = Vcat::find($this->vcat_id)->parent_id;
                return Vcat::find($prrent_id)->title;
            });
            $grid->vcat_id('部门')->display(function($vcat_id){
                return Vcat::find($vcat_id)->title;
            });
            $grid->province_id('省')->display(function($province_id){
                return $province_id?Province::find($province_id)->name:'';
            });
            $grid->name('参会人员')->editable();
            $grid->gravatar('头像')->image('', 100, 100);
            $grid->post_id('职务')->display(function($post_id) {
                return $post_id?Post::find($post_id)->name:'';
            });
            $grid->mobile('手机号')->editable();
            $grid->code('客户编码')->editable();
            $grid->company('客户')->editable();
            $grid->hotel('入住饭店')->display(function($hotel) {
                return $hotel?Hotel::find($hotel)->name:'';
            });
            $states = [
                'on' => ['text' => '是'],
                'off' => ['text' => '否'],
            ];
            $grid->column('switch_group','是否')->switchGroup([
                'has_attend' => '参加过订货会', 'is_need_sms' => '推送短信', 'is_enter' => '已报名'
            ], $states);

            $grid->salesman_id('业务员')->display(function($salesman_id) {
                return $salesman_id?Salesman::find($salesman_id)->name:'';
            });
            $grid->regional_manager_id('区域经理')->display(function($regional_manager_id) {
                return $regional_manager_id?Manager::find($regional_manager_id)->name:'';
            });


            $grid->has_sms('已发送短信')->display(function() {
                return $this->has_sms? '是':'否';
            });

            $grid->filter(function ($filter) {
                $filter->useModal();
                $filter->disableIdFilter();
                $filter->equal('vcat_id', '类别')
                    ->select(function () {
                        return Vcat::selectOptions();
                    });
                $filter->equal('is_enter', '是否报名')
                    ->select([0=>'否',1=>'是']);
                $filter->equal('has_attend', '是否参加过订货会')
                    ->select([0=>'否',1=>'是']);
                $filter->equal('has_sms', '是否发送过短信')
                    ->select([0=>'否',1=>'是']);
                $filter->like('name','参会人员');
                $filter->like('code','客户编码');
                $filter->like('hotel','入住酒店');
            });
            $grid->exporter(new CustomExporter());
            $grid->tools(function ($tools) {
                $tools->append(new DelCard());

                $tools->batch(function ($batch) {
                    $batch->add('批量报名', new BatchEnter(1));
                    $batch->add('批量发送短信', new BatchSend(1));
                });
            });
        });
    }

    public function create()
    {
        return Admin::content(function (Content $content) {
            $content->header('人员');
            $content->description('添加');
            $content->body($this->form());
        });
    }

    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('人员');
            $content->description(trans('admin::lang.edit'));
            $content->body($this->form()->edit($id));
        });
    }

    public function form()
    {
        return Vuser::form(function (Form $form) {

            $province_arr = Province::all()->pluck('name', 'id')->toArray();
            $post_arr = Post::all()->pluck('name', 'id')->toArray();
            $salesman_arr = Salesman::all()->pluck('name', 'id')->toArray();
            $manager_arr = Manager::all()->pluck('name', 'id')->toArray();
            $hotel_arr = Hotel::all()->pluck('name', 'id')->toArray();

            $province_arr[0] = '';
            ksort($province_arr);
            $post_arr[0] = '';
            ksort($post_arr);
            $salesman_arr[0] = '';
            ksort($salesman_arr);
            $manager_arr[0] = '';
            ksort($manager_arr);
            $hotel_arr[0] = '';
            ksort($hotel_arr);
            $form->select('vcat_id','类别')->options(Vcat::selectOptions())->rules('numeric|min:1');
            $form->select('province_id', '省')->options($province_arr);
            $form->text('name', '参会人员')->rules('required');
            $form->image('gravatar','头像')->move('',microtime().rand(0000,9999).".jpg");
            $form->select('post_id', '职务')->options($post_arr);
            $form->text('mobile', '手机号')->rules('required');
            $form->text('code', '客户编码');
            $form->text('card', '卡号');
            $form->select('salesman_id', '业务员')->options($salesman_arr);
            $form->switch('has_attend','参加过订货会');
            $form->switch('is_need_sms','推送短信');
            $form->switch('is_enter','报名');
            $form->select('regional_manager_id', '区域经理')->options($manager_arr);
            $form->text('company', '客户单位');
            $form->select('hotel', '入住酒店')->options($hotel_arr);
        });
    }

    public function enter(Request $request)
    {
        foreach (Vuser::find($request->get('ids')) as $vuser) {
            $vuser->is_enter = $request->get('action');
            $vuser->save();
        }
    }

    public function delCard(Request $request)
    {
        if ($request->ajax()) {
            DB::table('vusers')->update(['card' => '']);
        }
    }

    public function sendSms(Request $request)
    {
        if ($request->ajax()) {
            $flag = 1;
            //短信接口地址
            $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
            foreach (Vuser::find($request->get('ids')) as $vuser) {
                if ($vuser->is_need_sms && $vuser->mobile) {
                    $post_data = "account=C12631375&password=8156172ac4908193056621448e70d33a&mobile=".$vuser->mobile."&content=".
                        rawurlencode("您的验证码是：".'1234'."。请不要把验证码泄露给其他人。");

                    $responses =  xml_to_array(post($post_data, $target));
                    if($responses['code']==2){
                        $vuser->has_sms = $request->get('action');
                        $vuser->save();
                        $flag = 0;
                    }
                }
            }

            return response()->json([
                'errCode' => $flag,
                'response' => $responses
            ]);

        }
    }
}
