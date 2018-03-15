<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Validate;
/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;

    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with('group')
                    ->where($where)
                    ->order($sort, $order)
                    ->count();
            $list = $this->model
                    ->with('group')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $k => $v)
            {
                $v->password = '';
                $v->salt = '';
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill)
                {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $username = $params['username'];
                $password = $params['password'];
                $email = $params['email'];
                $mobile = $params['mobile'];

                $rule = [
                    'username'  => 'require|length:3,30',
                    'password'  => 'require|length:6,30',
                    'email'     => 'require|email',
                    'mobile'    => 'regex:/^1\d{10}$/',
                ];

                $msg = [
                    'username.require' => 'Username can not be empty',
                    'username.length'  => 'Username must be 3 to 30 characters',
                    'password.require' => 'Password can not be empty',
                    'password.length'  => 'Password must be 6 to 30 characters',
                    'email'            => 'Email is incorrect',
                    'mobile'           => 'Mobile is incorrect',
                ];
                $data = [
                    'username'  => $username,
                    'password'  => $password,
                    'email'     => $email,
                    'mobile'    => $mobile,
                ];
                $validate = new Validate($rule, $msg);
                $result = $validate->check($data);
                if (!$result)
                {
                    $this->error(__($validate->getError()));
                }
                if (\app\common\library\Auth::instance()->register($username, $password, $email, $mobile))
                {
                    $this->success(__('Sign up successful'));
                }
                else
                {
                    $this->error($this->auth->getError());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }


}
