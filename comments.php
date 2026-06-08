<?php
if ( post_password_required() ) return;
?>

<section class="comments-section" id="comments">

  <?php if ( have_comments() ) : ?>
    <h3 class="comments-title">
      <?php
      $count = get_comments_number();
      printf(
        $count === '1' ? '%s comment' : '%s comments',
        '<span class="comments-count">' . number_format_i18n( $count ) . '</span>'
      );
      ?>
    </h3>

    <ol class="comment-list">
      <?php
      wp_list_comments( [
        'style'       => 'ol',
        'short_ping'  => true,
        'avatar_size' => 40,
        'callback'    => 'smallpoles_comment',
      ] );
      ?>
    </ol>

    <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
    <nav class="comment-pagination">
      <?php paginate_comments_links(); ?>
    </nav>
    <?php endif; ?>

  <?php endif; ?>

  <?php if ( comments_open() ) : ?>
  <div class="comment-form-wrap">
    <h3 class="comment-form-title">Leave a comment</h3>
    <?php
    comment_form( [
      'title_reply'          => '',
      'title_reply_before'   => '',
      'title_reply_after'    => '',
      'comment_notes_before' => '',
      'comment_notes_after'  => '',
      'label_submit'         => 'Post Comment',
      'class_submit'         => 'comment-submit-btn',
      'class_form'           => 'comment-form',
      'fields' => [
        'author'  => '<p class="comment-field"><label for="author">Name <span class="comment-optional">(optional)</span></label><input id="author" name="author" type="text" placeholder="Anonymous" maxlength="60" /></p>',
        'email'   => '',
        'url'     => '',
        'cookies' => '',
      ],
      'comment_field' => '<p class="comment-field"><label for="comment">Your comment</label><textarea id="comment" name="comment" rows="4" placeholder="What\'s your take?" required maxlength="1500"></textarea></p>',
    ] );
    ?>
  </div>
  <?php elseif ( ! is_user_logged_in() ) : ?>
    <p class="comments-closed">Comments are closed for this post.</p>
  <?php endif; ?>

</section>
