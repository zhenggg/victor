<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;

use App\Vcat;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Form;
use Encore\Admin\Grid;

use Encore\Admin\Controllers\ModelForm;

use App\Conference;

class ConferencesController extends Controller
{
    use ModelForm;

    public function index()
    {
//        $date = Conference::pluck('date', 'date')
//            ->flatten(1)
//            ->all();

        return Admin::content(function (Content $content)  {
            $content->header('会议');
            $content->description('列表');
            $content->row(
                $this->grid()
            );
//            for ($i = 0; $i < ceil(count($date) / 2); $i++) {
//                $content->row(function (Row $row) use ($date, $i) {
//                    for ($key = $i * 2; $key <= $i * 2 + 1; $key++) {
//                        if (isset($date[$key])) {
//                            $row->column(6, function (Column $column) use ($date, $key) {
//                                $column->append((
//                                new Box(
//                                    $date[$key], $this->grid($date[$key])
//                                )));
//                            });
//                        }
//                    }
//                });
//            }
        });
    }

    protected function grid()
    {
        return Admin::grid(Conference::class, function (Grid $grid)  {
            //$grid->model()->where('date', '=', $date);
            $grid->column('time','时间')->display(function () {
                return $this->start_time.'-'.$this->end_time;
            });
            $grid->name('名称');
            $grid->description('地点')->display(
                function ($description) {
                    return '<pre>' . $description . '</pre>';
                }
            );
            $grid->vcats('参加该会议的单位')->pluck('title')->label();

            $grid->disableExport();
            $grid->disableBatchDeletion();
            $grid->disableRowSelector();
            $grid->disableFilter();
        });
    }

    public function create()
    {
        return Admin::content(function (Content $content) {
            $content->header('');
            $content->description('');
            $content->body($this->form());
        });
    }

    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('');
            $content->description(trans('admin::lang.edit'));
            $content->body($this->form()->edit($id));
        });
    }

    protected function form()
    {
        return Conference::form(function (Form $form) {
            $form->dateTimeRange('start_time', 'end_time', '时间范围');
            $form->text('name', '名称')->rules('required');
            $form->textarea('description', '地点')->placeholder('输入会议描述或地点（每句话结束请换行）')->rules('required');
            $form->multipleSelect('vcats','参加该会议的单位')
                ->options(Vcat::all()->where('is_father','=',0)->pluck('title', 'id'));
        });
    }
}
