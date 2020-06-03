<?php
/**
 * Created by PhpStorm
 * Author Zhiyong Dong <dongzy@xinruiying.com>
 * Date:2020/6/2
 * Time:16:51
 */

namespace app\controller;

use GuzzleHttp\Client;
use think\facade\Db;
use app\BaseController;

class Category extends BaseController
{

    //抓取网站的分类信息
    public function readJson0()
    {
        $json = file_get_contents('../runtime/category.json');
        $arr_data = json_decode($json, 1);
        $arr_category = $arr_data['menu']['first']['list'];

        $data = [];
        foreach ($arr_category as $arr_cate) {
            $arr = [];
            $arr['pid'] = 0;
            $arr['name'] = $arr_cate['gc_name'];
            $arr['layer'] = 0;
            $arr['gc_id'] = $arr_cate['gc_id'];
            $data[] = $arr;
        }
        Db::connect('jddb')
            ->table('spli_catogory')
            ->insertAll($data);
    }

    public function readJson1()
    {
        $json = file_get_contents('../runtime/category.json');
        $arr_data = json_decode($json, 1);
        $arr_category1 = $arr_data['menu']['second'];
        $where = [];
        $where['layer'] = 0;
        $where['pid'] = 0;
        $catory0 = Db::connect('jddb')
            ->table('spli_catogory')
            ->field('id,gc_id,name')
            ->where($where)
            ->select()
            ->toArray();
        //组成以gc_id为key以id为value的数组
        $db_arr0 = [];
        foreach ($catory0 as $cate0) {
            $db_arr0[$cate0['gc_id']] = $cate0['id'];
        }
        //把数组里面的二级分类取出来
        foreach ($db_arr0 as $k => $db_a0) {
            foreach ($arr_category1 as $key => $arr_cate1) {
                if ($k == $key) {
                    foreach ($arr_cate1 as $arr_cate11) {
                        //为一级分类添加二级分类
                        $arr = [];
                        $arr['pid'] = $db_a0;
                        $arr['name'] = $arr_cate11['gc_name'];
                        $arr['layer'] = 1;
                        $arr['gc_id'] = $arr_cate11['gc_id'];
                        Db::connect('jddb')->table('spli_catogory')->insert($arr);
                    }
                }
            }
        }
    }

    public function readJson2()
    {
        $json = file_get_contents('../runtime/category.json');
        $arr_data = json_decode($json, 1);
        $arr_category1 = $arr_data['menu']['second'];
        $where = [];
        $where['layer'] = 1;
        $catory1 = Db::connect('jddb')
            ->table('spli_catogory')
            ->field('id,gc_id,name')
            ->where($where)
            ->select()
            ->toArray();
        //组成以gc_id为key以id为value的数组
        $db_arr1 = [];
        foreach ($catory1 as $cate1) {
            $db_arr1[$cate1['gc_id']] = $cate1['id'];
        }
        //把json字符串的二级整出来数组
        $common = [];
        foreach ($arr_category1 as $arr_categor1) {
            foreach ($arr_categor1 as $arr_catego2) {
                foreach ($arr_catego2['child'] as $arr_categ2) {
                    $common[$arr_catego2['gc_id']][] = $arr_categ2;
                }

            }
        }

        //把数组里面的二级分类取出来
        foreach ($db_arr1 as $k => $db_a1) {
            foreach ($common as $key => $arr_cate2) {
                if ($k == $key) {
                    foreach ($arr_cate2 as $arr_cate22) {
                        //为一级分类添加二级分类
                        $arr = [];
                        $arr['pid'] = $db_a1;
                        $arr['name'] = $arr_cate22['gc_name'];
                        $arr['layer'] = 2;
                        if (isset($arr_cate22['imageUrl']) && $arr_cate22['imageUrl'] != '') {
                            $arr['img'] = 'http:' . $arr_cate22['imageUrl'];
                        }
                        $arr['gc_id'] = $arr_cate22['gc_id'];
                        Db::connect('jddb')->table('spli_catogory')->insert($arr);
                    }
                }
            }
        }
    }

    public function compareCategory()
    {
        $spli_ca = Db::connect('jddb')
                   ->table('spli_catogory')
                   ->field('id,name')
                   ->where('layer=0 and cm_goods_class_id=0')
                   ->select()
                   ->toArray();

        $cm_goods_class = Db::connect('crossbordermalldb')
                        ->table('cm_goods_class')
                        ->field('gc_id,gc_name')
                        ->where('level=0')
                        ->select()
                        ->toArray();
        //echo '<pre>';
        foreach ($spli_ca as $spli_c){
            foreach ($cm_goods_class as $cm_goods_clas){
                if($spli_c['name']==$cm_goods_clas['gc_name']){
                    //匹配到就更新字段cm_goods_class_id
                    Db::connect('jddb')
                        ->table('spli_catogory')
                        ->where('id='.$spli_c['id'])
                        ->save(['cm_goods_class_id'=>$cm_goods_clas['gc_id']]);
                }
            }
        }

    }
}
