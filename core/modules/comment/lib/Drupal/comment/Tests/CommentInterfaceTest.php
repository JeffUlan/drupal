<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentInterfaceTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentInterface;

/**
 * Tests the comment module administrative and end-user-facing interfaces.
 */
class CommentInterfaceTest extends CommentTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Comment interface',
      'description' => 'Test comment user interfaces.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests the comment interface.
   */
  function testCommentInterface() {
    // Set comments to have subject and preview disabled.
    $this->drupalLogin($this->admin_user);
    $this->setCommentPreview(DRUPAL_DISABLED);
    $this->setCommentForm(TRUE);
    $this->setCommentSubject(FALSE);
    $this->setCommentSettings('default_mode', COMMENT_MODE_THREADED, 'Comment paging changed.');
    $this->drupalLogout();

    // Post comment #1 without subject or preview.
    $this->drupalLogin($this->web_user);
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text);
    $this->assertTrue($this->commentExists($comment), 'Comment found.');

    // Set comments to have subject and preview to required.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_REQUIRED);
    $this->drupalLogout();

    // Create comment #2 that allows subject and requires preview.
    $this->drupalLogin($this->web_user);
    $subject_text = $this->randomName();
    $comment_text = $this->randomName();
    $comment = $this->postComment($this->node, $comment_text, $subject_text, TRUE);
    $this->assertTrue($this->commentExists($comment), 'Comment found.');

    // Comment as anonymous with preview required.
    $this->drupalLogout();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access content', 'access comments', 'post comments', 'skip comment approval'));
    $anonymous_comment = $this->postComment($this->node, $this->randomName(), $this->randomName(), TRUE);
    $this->assertTrue($this->commentExists($anonymous_comment), 'Comment found.');
    $anonymous_comment->delete();

    // Check comment display.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($subject_text, 'Individual comment subject found.');
    $this->assertText($comment_text, 'Individual comment body found.');

    // Set comments to have subject and preview to optional.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->setCommentSubject(TRUE);
    $this->setCommentPreview(DRUPAL_OPTIONAL);

    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $this->assertTitle(t('Edit comment @title | Drupal', array(
      '@title' => $comment->subject->value,
    )));

    // Test changing the comment author to "Anonymous".
    $comment = $this->postComment(NULL, $comment->comment_body->value, $comment->subject->value, array('name' => ''));
    $this->assertTrue(empty($comment->name->value) && $comment->uid->target_id == 0, 'Comment author successfully changed to anonymous.');

    // Test changing the comment author to an unverified user.
    $random_name = $this->randomName();
    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $comment = $this->postComment(NULL, $comment->comment_body->value, $comment->subject->value, array('name' => $random_name));
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($random_name . ' (' . t('not verified') . ')', 'Comment author successfully changed to an unverified user.');

    // Test changing the comment author to a verified user.
    $this->drupalGet('comment/' . $comment->id() . '/edit');
    $comment = $this->postComment(NULL, $comment->comment_body->value, $comment->subject->value, array('name' => $this->web_user->getUsername()));
    $this->assertTrue($comment->name->value == $this->web_user->getUsername() && $comment->uid->target_id == $this->web_user->id(), 'Comment author successfully changed to a registered user.');

    $this->drupalLogout();

    // Reply to comment #2 creating comment #3 with optional preview and no
    // subject though field enabled.
    $this->drupalLogin($this->web_user);
    // Deliberately use the wrong url to test
    // \Drupal\comment\Controller\CommentController::redirectNode().
    $this->drupalGet('comment/' . $this->node->id() . '/reply');
    // Verify we were correctly redirected.
    $this->assertUrl(url('comment/reply/node/' . $this->node->id() . '/comment', array('absolute' => TRUE)));
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment->id());
    $this->assertText($subject_text, 'Individual comment-reply subject found.');
    $this->assertText($comment_text, 'Individual comment-reply body found.');
    $reply = $this->postComment(NULL, $this->randomName(), '', TRUE);
    $reply_loaded = comment_load($reply->id());
    $this->assertTrue($this->commentExists($reply, TRUE), 'Reply found.');
    $this->assertEqual($comment->id(), $reply_loaded->pid->target_id, 'Pid of a reply to a comment is set correctly.');
    // Check the thread of reply grows correctly.
    $this->assertEqual(rtrim($comment->thread->value, '/') . '.00/', $reply_loaded->thread->value);

    // Second reply to comment #2 creating comment #4.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $comment->id());
    $this->assertText($comment->subject->value, 'Individual comment-reply subject found.');
    $this->assertText($comment->comment_body->value, 'Individual comment-reply body found.');
    $reply = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $reply_loaded = comment_load($reply->id());
    $this->assertTrue($this->commentExists($reply, TRUE), 'Second reply found.');
    // Check the thread of second reply grows correctly.
    $this->assertEqual(rtrim($comment->thread->value, '/') . '.01/', $reply_loaded->thread->value);

    // Reply to comment #4 creating comment #5.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $reply_loaded->id());
    $this->assertText($reply_loaded->subject->value, 'Individual comment-reply subject found.');
    $this->assertText($reply_loaded->comment_body->value, 'Individual comment-reply body found.');
    $reply = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $reply_loaded = comment_load($reply->id());
    $this->assertTrue($this->commentExists($reply, TRUE), 'Second reply found.');
    // Check the thread of reply to second reply grows correctly.
    $this->assertEqual(rtrim($comment->thread->value, '/') . '.01.00/', $reply_loaded->thread->value);

    // Edit reply.
    $this->drupalGet('comment/' . $reply->id() . '/edit');
    $reply = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $this->assertTrue($this->commentExists($reply, TRUE), 'Modified reply found.');

    // Confirm a new comment is posted to the correct page.
    $this->setCommentsPerPage(2);
    $comment_new_page = $this->postComment($this->node, $this->randomName(), $this->randomName(), TRUE);
    $this->assertTrue($this->commentExists($comment_new_page), 'Page one exists. %s');
    $this->drupalGet('node/' . $this->node->id(), array('query' => array('page' => 2)));
    $this->assertTrue($this->commentExists($reply, TRUE), 'Page two exists. %s');
    $this->setCommentsPerPage(50);

    // Attempt to reply to an unpublished comment.
    $reply_loaded->status->value = CommentInterface::NOT_PUBLISHED;
    $reply_loaded->save();
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment/' . $reply_loaded->id());
    $this->assertText(t('The comment you are replying to does not exist.'), 'Replying to an unpublished comment');

    // Attempt to post to node with comments disabled.
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => array(array('status' => COMMENT_HIDDEN))));
    $this->assertTrue($this->node, 'Article node created.');
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertText('This discussion is closed', 'Posting to node with comments disabled');
    $this->assertNoField('edit-comment', 'Comment body field found.');

    // Attempt to post to node with read-only comments.
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => array(array('status' => COMMENT_CLOSED))));
    $this->assertTrue($this->node, 'Article node created.');
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertText('This discussion is closed', 'Posting to node with comments read-only');
    $this->assertNoField('edit-comment', 'Comment body field found.');

    // Attempt to post to node with comments enabled (check field names etc).
    $this->node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1, 'comment' => array(array('status' => COMMENT_OPEN))));
    $this->assertTrue($this->node, 'Article node created.');
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertNoText('This discussion is closed', 'Posting to node with comments enabled');
    $this->assertField('edit-comment-body-0-value', 'Comment body field found.');

    // Delete comment and make sure that reply is also removed.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->deleteComment($comment);
    $this->deleteComment($comment_new_page);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertFalse($this->commentExists($comment), 'Comment not found.');
    $this->assertFalse($this->commentExists($reply, TRUE), 'Reply not found.');

    // Enabled comment form on node page.
    $this->drupalLogin($this->admin_user);
    $this->setCommentForm(TRUE);
    $this->drupalLogout();

    // Submit comment through node form.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('node/' . $this->node->id());
    $form_comment = $this->postComment(NULL, $this->randomName(), $this->randomName(), TRUE);
    $this->assertTrue($this->commentExists($form_comment), 'Form comment found.');

    // Disable comment form on node page.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->setCommentForm(FALSE);
  }
}
