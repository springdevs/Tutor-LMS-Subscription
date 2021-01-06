<?php

namespace Springdevs\TutorSubscrpt\Frontend;

use SpringDevs\WcSubscription\Illuminate\Helper;

/**
 * Class Tutor
 * @package Springdevs\TutorSubscrpt\Frontend
 */
class Tutor
{
    public function __construct()
    {
        add_filter('tutor_is_enrolled', [$this, 'tutor_is_enrolled'], 10, 3);
        add_filter('woocommerce_product_add_to_cart_url', [$this, "change_add_to_cart_url"], 10, 2);
        add_filter('woocommerce_loop_add_to_cart_args', [$this, "change_add_cart_args"], 10, 2);
        add_filter('woocommerce_product_add_to_cart_text', [$this, "change_cart_txt"], 10, 2);
        add_filter('tutor_get_template_path', [$this, "change_template_path"], 10, 2);
        add_filter('tutor_filter_single_course_product_id', [$this, "filter_product_id"], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, "change_single_add_to_cart_txt"], 10, 2);
    }

    public function tutor_is_enrolled($getEnrolledInfo, $course_id, $user_id)
    {
        $product_id = tutils()->get_course_product_id($course_id);
        if ($getEnrolledInfo) {
            $order_id = get_post_meta($getEnrolledInfo->ID, "_tutor_enrolled_by_order_id", true);
            if ($order_id) {
                $order = wc_get_order($order_id);
                foreach ($order->get_items() as $order_item) {
                    if ($order_item['product_id'] === $product_id) {
                        $bulk_product = $order_item->get_meta("_bulk_product");
                        if ($bulk_product) {
                            return $this->check_subscription($bulk_product, $getEnrolledInfo);
                        } else {
                            if ($product_id) return $this->check_subscription($product_id, $getEnrolledInfo);
                        }
                    }
                }
            }
        } else {
            if ($product_id) {
                return $this->check_subscription($product_id, $getEnrolledInfo);
            }
        }

        return $getEnrolledInfo;
    }

    public function check_subscription($product_id, $getEnrolledInfo)
    {
        $product = wc_get_product($product_id);
        if (!$product->is_type("variable")) {
            $post_meta = get_post_meta($product_id, 'subscrpt_general', true);
            if (is_array($post_meta) && $post_meta['enable']) {
                $active_items = get_user_meta(get_current_user_id(), '_subscrpt_active_items', true);
                if (!is_array($active_items)) $active_items = [];
                foreach ($active_items as $active_item) {
                    if ($active_item['product'] == $product_id) return $getEnrolledInfo;
                }
                return false;
            }
        }
        return $getEnrolledInfo;
    }

    public function change_add_to_cart_url($link, $product)
    {
        $bulk_parent_product = $this->check_if_product_in_bulk($product);
        if ($bulk_parent_product) $link = "?add-to-cart=" . $bulk_parent_product;
        return $link;
    }

    public function change_add_cart_args($args, $product)
    {
        $bulk_parent_product = $this->check_if_product_in_bulk($product);
        if ($bulk_parent_product) $args['attributes']['data-product_id'] = $bulk_parent_product;
        return $args;
    }

    public function change_cart_txt($text, $product)
    {
        $bulk_parent_product = $this->check_if_product_in_bulk($product);
        if ($bulk_parent_product) $text = "renew";
        return $text;
    }

    public function check_if_product_in_bulk($product)
    {
        if (!is_user_logged_in()) return false;
        $current_user = wp_get_current_user();
        $product_id = $product->get_id();
        if (wc_customer_bought_product($current_user->user_email, $current_user->ID, $product_id)) {
            $expired_items = get_user_meta(get_current_user_id(), '_subscrpt_expired_items', true);
            if (!is_array($expired_items)) $expired_items = [];
            foreach ($expired_items as $expired_item) {
                $bulk_products = get_post_meta($expired_item['product'], '_bpselling_bulk_products', true);
                if (!$bulk_products && !is_array($bulk_products)) $bulk_products = [];
                foreach ($bulk_products as $bulk_product) if ($bulk_product == $product_id) return $expired_item['product'];
            }
        }
        return false;
    }

    public function change_template_path($path, $template)
    {
        if ($template === 'single\course\add-to-cart-woocommerce') {
            $path = TUTOR_SUBSCRPT_PATH . "/templates/single/add-to-cart-woocommerce.php";
        }
        return $path;
    }

    public function filter_product_id($product_id, $course_id)
    {
        $product = wc_get_product($product_id);
        if (!$product) return $product_id;
        $bulk_parent_product = $this->check_if_product_in_bulk($product);
        if ($bulk_parent_product) return $bulk_parent_product;
        return $product_id;
    }

    public function change_single_add_to_cart_txt($text, $product)
    {
        $expired_items = get_user_meta(get_current_user_id(), '_subscrpt_expired_items', true);
        if (!is_array($expired_items)) $expired_items = [];
        foreach ($expired_items as $expired_item) if ($expired_item['product'] === $product->get_id()) return "renew";
        return $text;
    }
}
