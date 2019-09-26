<?php
  /**
   * user.php
   *
   * @package irOClassApi
   * @author Ruslan Ismailov r3d_time@hotmail.com
   * @copyright 2019
   * @version v1.00 24/09/2019
   */

  /**
   * ir_login function.
   *
   * @access public
   * @return void
   */
  function ir_login() {
    $do = new IrCWebLogin();
    $do->doModel();
  }

  function ir_vipadds() {
    $manager = Item::newInstance();
    $result = $manager->dao->query("SELECT item.pk_i_id AS aid, item.fk_i_category_id AS cit,
        item.s_contact_name AS owner, descr.s_title AS title, catd.s_name AS category,
        item.i_price AS price, SUBSTR(TRIM(CHAR(9) FROM TRIM(descr.s_description)), 1, 50) AS text,
        loc.s_city_area AS metro,
        CONCAT('https://yntymak.ru/', res.s_path, res.pk_i_id, '_preview.jpg') AS path
        FROM oc_t_item item
        INNER JOIN oc_t_item_resource res ON res.fk_i_item_id = item.pk_i_id
        INNER JOIN oc_t_item_location loc ON loc.fk_i_item_id = item.pk_i_id
        INNER JOIN oc_t_item_description descr ON descr.fk_i_item_id = item.pk_i_id
        INNER JOIN oc_t_category_description catd ON item.fk_i_category_id = catd.fk_i_category_id
        WHERE item.b_premium = 1 AND item.b_enabled = 1 AND item.b_active = 1
        ORDER BY item.dt_pub_date DESC LIMIT 20");
    $arr = $result->result();
    irb_send( $arr, true );
  }

  function ir_categories() {
    $manager = Item::newInstance();
    $ext = irb_getSettingsForMainCatTab();
    $result = $manager->dao->query("SELECT cat.pk_i_id AS cid, descr.s_name AS name
        FROM oc_t_category cat
        INNER JOIN oc_t_category_description descr ON descr.fk_i_category_id = cat.pk_i_id
        WHERE cat.fk_i_parent_id IS NULL");
    $categories = $result->result();
    for ($i = 0; $i < count($categories); $i++) {
      if (isset($ext['cat' . $categories[$i]['cid']])) {
        $categories[$i]['visible'] = isset($ext['cat' . $categories[$i]['cid']]['visible'])
          ? $ext['cat' . $categories[$i]['cid']]['visible'] : 1;
        $categories[$i]['icon'] = isset($ext['cat' . $categories[$i]['cid']]['icon'])
          ? $ext['cat' . $categories[$i]['cid']]['icon'] : 'home_work';
      }
      $result = $manager->dao->query("SELECT cat.pk_i_id AS cid, descr.s_name AS name
        FROM oc_t_category cat
        INNER JOIN oc_t_category_description descr ON descr.fk_i_category_id = cat.pk_i_id
        WHERE cat.fk_i_parent_id =" . $categories[$i]['cid']);
      $categories[$i]['children'] = $result->result();
      if (count($categories[$i]['children']) > 0) {
        for ($c = 0; $c < count($categories[$i]['children']); $c++) {
          if (isset($ext['cat' . $categories[$i]['children'][$c]['cid']])) {
            $categories[$i]['children'][$c]['visible'] = isset($ext['cat' . $categories[$i]['children'][$c]['cid']]['visible'])
              ? $ext['cat' . $categories[$i]['children'][$c]['cid']]['visible'] : 1;
          } else {
            $categories[$i]['children'][$c]['visible'] = 1;
          }
        }
      }
    }
    irb_send( $categories, true );
  }

  function ir_adds() {
    $cid = isset($_GET['cid']) ? intval($_GET['cid']) : false;
    $sql = "SELECT item.pk_i_id AS aid, item.fk_i_category_id AS cit,
    item.s_contact_name AS owner, descr.s_title AS title, catd.s_name AS category,
    item.i_price AS price, SUBSTR(TRIM(CHAR(9) FROM TRIM(descr.s_description)), 1, 50) AS text,
    loc.s_city_area AS metro,
    CONCAT('https://yntymak.ru/', res.s_path, res.pk_i_id, '_preview.jpg') AS path
    FROM oc_t_item item
    INNER JOIN oc_t_item_resource res ON res.fk_i_item_id = item.pk_i_id
    INNER JOIN oc_t_item_location loc ON loc.fk_i_item_id = item.pk_i_id
    INNER JOIN oc_t_item_description descr ON descr.fk_i_item_id = item.pk_i_id
    INNER JOIN oc_t_category_description catd ON item.fk_i_category_id = catd.fk_i_category_id
    WHERE item.b_enabled = 1 AND item.b_active = 1";
    if ($cid) {
      $sql = $sql . "  AND item.fk_i_category_id = " . $cid;
    }
    $sql = $sql . " ORDER BY item.dt_pub_date DESC LIMIT 20";
    $manager = Item::newInstance();
    $result = $manager->dao->query($sql);
    $ads = $result->result();
    irb_send( $ads, true );
  }