<?php
/**
 * Created by PhpStorm
 * Author Zhiyong Dong <dongzy@xinruiying.com>
 * Date:2020/6/2
 * Time:13:43
 */

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Jflm extends BaseController {

    /**
     * 查询畅游订单号对应的商品信息 主要用来给畅游运营看 让他去甄别错误类目
     */
    public function showSkus(){
        $common = [];
        $order_no = $_GET['order_no'];
        $ct = 1577808000000;
        $where = [];
        $where['order_no'] = $order_no;
        $where['payment_id'] = 96;
        //$where['is_jflm_push'] = 0;
        $order = Db::connect('orderdb')
                 ->table('orders')
                 ->field('order_no')
                 ->where($where)
                 ->whereRaw('ct>:ct',['ct'=>$ct])
                 ->whereIn('status',[3,4])
                 ->whereNull('m_order_no')
                 ->find();

        //echo Db::connect('orderdb')->getLastSql();
        // 先拿主表查
        $goods_list = Db::connect('orderdb')
                      ->table('order_goods')
                      ->field('goods_id,category1,name,order_no')
                      ->where(['order_no'=>$order['order_no']])
                      ->select()
                      ->toArray();

        if(empty($goods_list)){
            //查不到说明 订单进行了拆分 我们拿到子单去查
            $orders = Db::connect('orderdb')
                      ->table('orders')
                      ->field('order_no')
                      ->where(['m_order_no'=>$order['order_no']])
                      ->select()
                      ->toArray();
            if(empty($orders)){
                die('数据不完整');
            }
            $arr_sub_orders = [];
            foreach ($orders as $ord){
                $arr_sub_orders[]= $ord['order_no'];
            }
            $order_goods = Db::connect('orderdb')
                ->table('order_goods')
                ->field('goods_id,category1,name,order_no')
                ->whereIn('order_no',$arr_sub_orders)
                ->select()
                ->toArray();
            foreach ($order_goods as $key=>$order_good){
                $common[$key]['subOrderId'] = $order_good['order_no'].'-'.$order_good['goods_id'];
                $common[$key]['goodsType'] = $order_good['category1'];
                $common[$key]['goodsId'] = $order_good['goods_id'];
                $common[$key]['goodsName'] = $order_good['name'];
            }
        }
        else{
            foreach ($goods_list as $key=>$good_list){
                $common[$key]['subOrderId'] = $good_list['order_no'].'-'.$good_list['goods_id'];
                $common[$key]['goodsType'] = $good_list['category1'];
                $common[$key]['goodsId'] = $good_list['goods_id'];
                $common[$key]['goodsName'] = $good_list['name'];
            }
        }
        echo json_encode($common);
        die;
        //echo '<pre>';print_r($common);die;
    }
}
