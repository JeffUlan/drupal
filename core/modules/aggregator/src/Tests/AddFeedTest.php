<?php

/**
 * @file
 * Definition of Drupal\aggregator\Tests\AddFeedTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Add feed test.
 *
 * @group aggregator
 */
class AddFeedTest extends AggregatorTestBase {
  /**
   * Creates and ensures that a feed is unique, checks source, and deletes feed.
   */
  function testAddFeed() {
    $feed = $this->createFeed();
    $feed->refreshItems();

    // Check feed data.
    $this->assertUrl(\Drupal::url('aggregator.feed_add', [], ['absolute' => TRUE]), [], 'Directed to correct url.');
    $this->assertTrue($this->uniqueFeed($feed->label(), $feed->getUrl()), 'The feed is unique.');

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, 'Feed source exists.');
    $this->assertText($feed->label(), 'Page title');
    $this->assertRaw($feed->getWebsiteUrl());

    // Try to add a duplicate.
    $edit = [
      'title[0][value]' => $feed->label(),
      'url[0][value]' => $feed->getUrl(),
      'refresh' => '900',
    ];
    $this->drupalPostForm('aggregator/sources/add', $edit, t('Save'));
    $this->assertRaw(t('A feed named %feed already exists. Enter a unique title.', array('%feed' => $feed->label())));
    $this->assertRaw(t('A feed with this URL %url already exists. Enter a unique URL.', array('%url' => $feed->getUrl())));

    // Delete feed.
    $this->deleteFeed($feed);
  }

  /**
   * Tests feeds with very long URLs.
   */
  function testAddLongFeed() {
    // Create a feed with a URL of > 255 characters.
    $long_url = "https://www.google.com/search?ix=heb&sourceid=chrome&ie=UTF-8&q=angie+byron#sclient=psy-ab&hl=en&safe=off&source=hp&q=angie+byron&pbx=1&oq=angie+byron&aq=f&aqi=&aql=&gs_sm=3&gs_upl=0l0l0l10534l0l0l0l0l0l0l0l0ll0l0&bav=on.2,or.r_gc.r_pw.r_cp.,cf.osb&fp=a70b6b1f0abe28d8&biw=1629&bih=889&ix=heb";
    $feed = $this->createFeed($long_url);
    $feed->refreshItems();

    // Create a second feed of > 255 characters, where the only difference is
    // after the 255th character.
    $long_url_2 = "https://www.google.com/search?ix=heb&sourceid=chrome&ie=UTF-8&q=angie+byron#sclient=psy-ab&hl=en&safe=off&source=hp&q=angie+byron&pbx=1&oq=angie+byron&aq=f&aqi=&aql=&gs_sm=3&gs_upl=0l0l0l10534l0l0l0l0l0l0l0l0ll0l0&bav=on.2,or.r_gc.r_pw.r_cp.,cf.osb&fp=a70b6b1f0abe28d8&biw=1629&bih=889";
    $feed_2 = $this->createFeed($long_url_2);
    $feed->refreshItems();

    // Check feed data.
    $this->assertTrue($this->uniqueFeed($feed->label(), $feed->getUrl()), 'The first long URL feed is unique.');
    $this->assertTrue($this->uniqueFeed($feed_2->label(), $feed_2->getUrl()), 'The second long URL feed is unique.');

    // Check feed source.
    $this->drupalGet('aggregator/sources/' . $feed->id());
    $this->assertResponse(200, 'Long URL feed source exists.');
    $this->assertText($feed->label(), 'Page title');

    // Delete feeds.
    $this->deleteFeed($feed);
    $this->deleteFeed($feed_2);
  }
}
