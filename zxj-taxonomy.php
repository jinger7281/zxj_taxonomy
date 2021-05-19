<?php
/**
 * @author            ZhengXiaojing
 * @copyright         2019 ZhengXiaojing
 * @license           GPL-2.0
 *
 * @wordpress-plugin
 * Plugin Name:       Taxonomy(Categories) thumb
 * Plugin URI:        http://www.zhengxiaojing.cn
 * Description:       By this plugin you can add custom images for taxonomy(now for category)
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            ZhengXiaojing
 * Author URI:        http://www.zhengxiaojing.cn
 * Text Domain:       zxj-taxonomy
 * License:           GPL v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace zxj\taxonomy;

const THUMB_META_KEY = 'zxj-thumbnails';
const NS_PRE = 'zxj\taxonomy\\';

function on_activation()
{
    // TODO install related
}

function on_deactivation()
{
    // TODO deactivation related
}

function on_uninstall()
{
    $terms = get_terms([
        'taxonomy' => 'category',
        'fields' => 'ids'
    ]);
    if(!is_array($terms)){
        return;
    }
    foreach($terms as $v) {
        delete_term_meta($v, THUMB_META_KEY);
    }
}

\register_activation_hook(__FILE__, NS_PRE . 'on_activation');
\register_deactivation_hook(__FILE__, NS_PRE . 'on_deactivation');
\register_uninstall_hook(__FILE__, NS_PRE . 'on_uninstall');

function taxonomy_form_fields($taxonomy)
{
    \wp_enqueue_media();
    $term_id = 0;
    if(is_object($taxonomy)){
        $term_id = $taxonomy->term_id;
    }
    form_view($term_id);
}

\add_action('category_add_form_fields', NS_PRE . 'taxonomy_form_fields', 10, 1);
\add_action('category_edit_form_fields', NS_PRE . 'taxonomy_form_fields', 10, 1);
/**
 * @param int $from 0 add_form_field格式 1 edit_form_field 格式
 */
function form_view($term_id)
{
?>
    <style type="text/css">
        .zxj-add-image {
            border: 4px dashed #cecece;
            width: 80px;
            height: 80px;
            position: relative;
            cursor: pointer;
        }

        .add-image-h-line {
            width: 50%;
            height: 4px;
            background: #cecece;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            margin: auto;
        }

        .add-image-v-line {
            width: 4px;
            height: 50%;
            background: #cecece;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            margin: auto;
        }

        .zxj-image-preview {
            overflow: hidden;
        }

        .zxj-inner-image {
            width: 100%;
            height: 100%;
        }

        .zxj-preview-del-button {
            position: absolute;
            top: 0;
            bottom: 0;
            right: 0;
            left: 0;
            margin: auto;
            background: cornflowerblue;
            color: white;
            cursor: pointer;
            width: 40px;
            height: 20px;
            border-radius: 4px;
        }

        .zxj-preview-image-container {
            padding: 5px;
            float: left;
            width: 80px;
            height: 80px;
            text-align: center;
            position: relative;
        }
    </style>
    <?php if ($term_id === 0) : ?>
        <div class="form-field">
            <label>分类图片</label>
            <div class="zxj-image-preview" style="overflow: hidden;"></div>
            <div class="zxj-add-image">
                <div class="add-image-h-line"></div>
                <div class="add-image-v-line"></div>
            </div>
        </div>
    <?php else : ?>
        <tr class="form-field">
            <th>
                <label>分类图片</label>
            </th>
            <td>
                <div class="zxj-image-preview">
                    <?php
                    $thumbnails = \get_term_meta($term_id, 'zxj-thumbnails', true);
                    if (\is_array($thumbnails)) :
                        foreach ($thumbnails as $v) :
                    ?>
                            <div class="zxj-preview-image-container">
                                <input type="hidden" name="taxonomy_images[]" value="<?php echo $v; ?>">
                                <img src="<?php echo $v; ?>" class="zxj-inner-image">
                                <span class="zxj-preview-del-button" onclick="delSelf.bind(this)()">删除</span>
                            </div>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
                <div class="zxj-add-image">
                    <div class="add-image-h-line"></div>
                    <div class="add-image-v-line"></div>
                </div>
            </td>
        </tr>
    <?php endif; ?>
    <script type="text/javascript">
        jQuery(function($) {
            function delSelf() {
                var parent = $(this).parent();
                parent.remove();
                var url = parent.find('img').attr('src');
                parent.find('input[value="' + url + '"]').remove();
            }
            window.delSelf = delSelf;

            function createNewDom(url) {
                var parent = document.createElement('div');
                var image = document.createElement('img');
                var delButton = document.createElement('span');
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'taxonomy_images[]';
                hidden.value = url;
                delButton.onclick = delSelf;
                parent.className = 'zxj-preview-image-container';
                image.className = 'zxj-inner-image';
                delButton.className = 'zxj-preview-del-button';
                delButton.innerText = '删除';
                image.src = url;
                parent.appendChild(image);
                parent.appendChild(delButton);
                parent.appendChild(hidden);
                $('.zxj-image-preview').append(parent);
            }

            var frame = wp.media({
                title: '选择图片',
                button: {
                    text: '使用选中图片'
                },
                multiple: true
            });
            $('.zxj-add-image').on('click', function() {
                frame.open();
            });
            frame.on('select', function() {
                try {
                    var images = frame.state().get('selection').toJSON();
                    for (var idx in images) {
                        if (!images.hasOwnProperty(idx)) {
                            continue;
                        }
                        var current = images[idx];
                        createNewDom(current.url);
                    }
                } catch (e) {
                    alert('There are some errors happend when select image, please check console panel for detail');
                    console.log(e);
                }
            });
        });
    </script>
<?php
}
?>
<?php
function on_save_taxonomy($term_id)
{
    $images = isset($_POST['taxonomy_images']) ? $_POST['taxonomy_images'] : null;
    if (!\is_array($images)) {
        \delete_term_meta($term_id, THUMB_META_KEY);
        return;
    }
    foreach($images as $k => $v) {
        $images[$k] = strip_tags($v);
    }
    $origin_meta = \get_term_meta($term_id, THUMB_META_KEY, true);
    if (\is_array($origin_meta)) {
        \update_term_meta($term_id, THUMB_META_KEY, $images);
    } else {
        \add_term_meta($term_id, THUMB_META_KEY, $images);
    }
}
add_action('edit_term', NS_PRE . 'on_save_taxonomy', 10, 1);
add_action('create_term', NS_PRE . 'on_save_taxonomy', 10, 1);