<?php 


defined('BASEPATH') or exit( 'No direct script access allowed' );

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index($cid = 0, $page = "null")
    {
        if( !defined("Web_On") || Web_On == 0 ) 
        {
            exit( "Hello World" );
        }

        $this->load->database();
        $cid = (int) $cid;
        if( $page == "null" ) 
        {
            $page = $cid;
        }
        else
        {
            $page = (int) $page;
        }

        if( $page == 0 ) 
        {
            $page = 1;
        }

        $where_str = (0 < $cid ? "where cid=" . $cid : "");
        $per_page = 20;
        $sel_sql = "select * from " . CS_SqlPrefix . "video " . $where_str . " order by id desc";
        $tol_sql = "select count(*) as count from " . CS_SqlPrefix . "video " . $where_str;
        $total_res = $this->db->query($tol_sql)->row();
        $total = $total_res->count;
        $totalPages = (ceil($total / $per_page) ? ceil($total / $per_page) : 1);
        $page = ($totalPages < $page ? $totalPages : $page);
        if( $total < $per_page ) 
        {
            $per_page = $total;
        }

        $sel_sql .= " limit " . $per_page * ($page - 1) . "," . $per_page;
        $query = $this->db->query($sel_sql)->result();
        $_pageNum = 5;
        $pages = ($page < $totalPages ? $totalPages : $page);
        $pages = ($totalPages < $page ? $page : $totalPages);
        $_start = $page - floor($_pageNum / 2);
        $_start = ($_start < 1 ? 1 : $_start);
        $_end = $page + floor($_pageNum / 2);
        $_end = ($pages < $_end ? $pages : $_end);
        $_curPageNum = $_end - $_start + 1;
        if( $_curPageNum < $_pageNum && 1 < $_start ) 
        {
            $_start = $_start - ($_pageNum - $_curPageNum);
            $_start = ($_start < 1 ? 1 : $_start);
            $_curPageNum = $_end - $_start + 1;
        }

        if( $_curPageNum < $_pageNum && $_end < $pages ) 
        {
            $_end = $_end + $_pageNum - $_curPageNum;
            $_end = ($pages < $_end ? $pages : $_end);
        }

        $data["cid"] = $cid;
        $data["page"] = $page;
        $data["pagestart"] = $_start;
        $data["pageend"] = $_end;
        $data["pagejs"] = $totalPages;
        $data["pagenum"] = $total;
        $data["video"] = $query;
        $data["vlist"] = $this->db->query("select * from " . CS_SqlPrefix . "class order by id asc limit 6")->result();
        $this->load->view("home.html", $data);
    }

}



