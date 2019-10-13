<?php 




defined('BASEPATH') or exit( 'No direct script access allowed' );

class Vlist extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model("User");
        $this->User->Login();
    }

    public function index()
    {
        $page = intval($this->input->get_post("page", true));
        $page = ($page < 1 ? 1 : $page);
        $per_page = 16;
        $where = array(  );
        $key = $this->input->get_post("key", true);
        $where_str = "";
        if( !empty($key) ) 
        {
            $where_str = " where name like '%" . $key . "%'";
        }

        $sel_sql = "select * from " . CS_SqlPrefix . "class " . $where_str . " order by id desc";
        $tol_sql = "select count(*) as count from " . CS_SqlPrefix . "class " . $where_str;
        $total_res = $this->db->query($tol_sql)->row();
        $total = $total_res->count;
        $totalPages = (ceil($total / $per_page) ? ceil($total / $per_page) : 1);
        $page = ($totalPages < $page ? $totalPages : $page);
        $data["nums"] = $total;
        if( $total < $per_page ) 
        {
            $per_page = $total;
        }

        $sel_sql .= " limit " . $per_page * ($page - 1) . "," . $per_page;
        $data["vlist"] = $this->db->query($sel_sql)->result();
        $base_url = site_url("vlist") . "?key=" . $key . "&page=";
        $data["page_data"] = page_data($total, $page, $totalPages);
        $data["page_list"] = admin_page($base_url, $page, $totalPages);
        $data["page"] = $page;
        $data["key"] = $key;
        $this->load->view("vlist.html", $data);
    }

    public function edit($id = 0)
    {
        $id = intval($id);
        if( 0 < $id ) 
        {
            $vod = $this->db->query("select * from " . CS_SqlPrefix . "class where id=" . $id)->row();
            $data["name"] = $vod->name;
            $data["xid"] = $vod->xid;
            $data["id"] = $vod->id;
        }
        else
        {
            $data["name"] = "";
            $data["xid"] = 10;
            $data["id"] = 0;
        }

        $this->load->view("vlist_edit.html", $data);
    }

    public function save($act ='add',$id=0)
    {
    
        $id = intval($id);
        $data["name"] = $this->input->post("name", true);
        $data["xid"] = (int) $this->input->post("xid", true);
        $data["id"] = (int) $this->input->post("fid", true);
       
        if( empty($data["name"]) ) 
        {
            getjson("分类名字不能为空");
        }

        if( $act == 'add' ) 
        {
          $this->db->insert("class", $data);
        }
        else
        {
            $this->db->update("class", $data, array( "id" => $id ));
        }

        getjson($info, 0);
    }

    public function del()
    {
        $ids = $this->input->get_post("id");
        if( is_array($ids) ) 
        {
            array_unique($ids);
            $ids = array_merge($ids);
            if( sizeof($ids) < 1 ) 
            {
                getjson("请选择要删除的文件");
            }

            foreach( $ids as $key => $value ) 
            {
                $id = intval($value);
                if( $id < 1 ) 
                {
                    continue;
                }

                $this->db->delete("class", array( "id" => $id ));
            }
        }
        else
        {
            $id = intval($ids);
            $this->db->delete("class", array( "id" => $id ));
        }

        $info["url"] = site_url("class") . "?v=" . rand(0, 999);
        getjson($info, 0);
        return NULL;
    }

}



