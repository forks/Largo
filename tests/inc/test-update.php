<?php

class UpdateTestFunctions extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		of_reset_options();
#		$this->term_id = $this->factory->term->create( array(
#			'name' => 'Prominence',
#			'taxonomy' => 'prominence',
#			'slug' => 'term-slug'
#		));
		$this->term_ids = $this->factory->term->create_many( 10, array(
			'taxonomy' => 'prominence'
		));
	}
	function test_largo_version() {
		// depends upon wp_get_theme,
		//   depends upon get_stylesheet
		//   depends upon get_raw_theme_root

		// We want some output
		$return = largo_version();
		$this->assertTrue(isset($return));
	}
	function test_largo_need_updates() {
		// requires largo_version

		// force updates by setting current version of largo to 0.0
		of_set_option('largo_version', '0.0');

		// Run updates!
		$this->assertTrue(largo_need_updates());

		// Will we ever hit this version number?
		of_set_option('largo_version', '999.999');

		// Run updates!
		$this->assertFalse(largo_need_updates());

		of_reset_options();
	}
	
	function test_largo_perform_update() {
		// requires largo_need_updates

		// Backup sidebar widgets, create our own empty sidebar
		$widgets_backup = get_option( 'sidebars_widgets ');
		update_option( 'sidebars_widgets', array(
			'article-bottom' => array (), // largo_instantiate_widget uses article-bottom for all its widgets
		) );

		// force updates by setting current version of largo to 0.0
		of_set_option('largo_version', '0.0');

		largo_perform_update();

		// check that options have been set
		$this->assertEquals('classic', of_get_option('single_template'));
		$this->assertEquals(largo_version(), of_get_option('largo_version'));

		// Cleanup
		of_reset_options();
		delete_option('sidebars_widgets');
		update_option('sidebars_widgets', $widgets_backup);
		unset($widgets_backup);
	}
	function test_largo_home_transition() {
		// old topstories
		of_reset_options();
		of_set_option('homepage_top', 'topstories');
		largo_home_transition();
		$this->assertEquals('TopStories', of_get_option('home_template', 0));
		
		// old slider
		of_reset_options();
		of_set_option('homepage_top', 'slider');
		largo_home_transition();
		$this->assertEquals('HomepageBlog', of_get_option('home_template', 0));
		
		// old blog
		of_reset_options();
		of_set_option('homepage_top', 'blog');
		largo_home_transition();
		$this->assertEquals('HomepageBlog', of_get_option('home_template', 0));
		
		// Anything else
		of_reset_options();
		of_set_option('', 'slider');
		largo_home_transition();
		$this->assertEquals('HomepageBlog', of_get_option('home_template', 0));
		
		// Not actually set
		of_reset_options();
		largo_home_transition();
		$this->assertEquals('HomepageBlog', of_get_option('home_template', 0));
	}
	function test_largo_update_widgets() {
		// uses largo_widget_in_region
		// uses largo_instantiate_widget

		// Backup sidebar widgets, create our own empty sidebar
		$widgets_backup = get_option( 'sidebars_widgets ');
		update_option( 'sidebars_widgets', array(
			'article-bottom' => array (), // largo_instantiate_widget uses article-bottom for all its widgets
		) );

		// These should all trigger a widget addition.
		of_set_option('social_icons_display', 'both');
		of_set_option('in_series', NULL);
		of_set_option('show_tags', 1);
		of_set_option('show_author_box', 1);
		of_set_option('show_related_content', 1);
		of_set_option('show_next_prev_nav_single', 1);
		
		largo_update_widgets();
		
		// Check for expected output
		// These are all #2 because they are the first instance of the widget
		$widgets = get_option('sidebars_widgets');
		$this->assertEquals('largo-follow-widget-2', $widgets['article-bottom'][0]);
		$this->assertEquals('largo-post-series-links-widget-2', $widgets['article-bottom'][1]);
		$this->assertEquals('largo-tag-list-widget-2', $widgets['article-bottom'][2]);
		$this->assertEquals('largo-author-bio-widget-2', $widgets['article-bottom'][3]);
		$this->assertEquals('largo-explore-related-widget-2', $widgets['article-bottom'][4]);
		$this->assertEquals('largo-prev-next-post-links-widget-2', $widgets['article-bottom'][5]);

		// Cleanup
		of_reset_options();
		delete_option('sidebars_widgets');
		update_option('sidebars_widgets', $widgets_backup);
		unset($widgets_backup);
	}
	function test_largo_widget_in_region() {
		// uses WP_Error
		$widgets_backup = get_option( 'sidebars_widgets ');

		// A widget that exists in a sidebar that does
		update_option( 'sidebars_widgets', array(
			'sidebar-single' => array (
				0 => 'archives-2',
			),
		) );
		$return = largo_widget_in_region('archives', 'sidebar-single');
		$this->assertTrue($return);
		unset($return);

		// A widget that does not exist in a sidebar that does
		update_option( 'sidebars_widgets', array(
			'sidebar-single' => array (
				0 => 'archives-2',
			),
		) );
		$return = largo_widget_in_region('wodget', 'sidebar-single');
		$this->assertFalse($return);
		unset($return);

		// A widget that exists in a sidebar that does not
		update_option( 'sidebars_widgets', array(
			'sidebar-single' => array (
				0 => 'archives-2',
			),
		) );
		$return = largo_widget_in_region('archives', 'missing-region');
		$this->assertTrue($return instanceof WP_Error);
		unset($return);

		// A widget that does not exist in a sidebar that also does not
		update_option( 'sidebars_widgets', array(
			'sidebar-single' => array (
				0 => 'archives-2',
			),
		) );
		$return = largo_widget_in_region('wodget', 'missing-region');
		$this->assertTrue($return instanceof WP_Error);
		unset($return);

		// Cleanup
		delete_option('sidebars_widgets');
		update_option('sidebars_widgets', $widgets_backup);
		unset($widgets_backup);
	}
	function test_largo_instantiate_widget() {
		// uses wp_parse_args, available here
		// uses update_option, available here
		$widgets_backup = get_option( 'sidebars_widgets ');
		update_option( 'sidebars_widgets', array(
			'article-bottom' => array (),
		) );

		// instantiate the widget
		largo_instantiate_widget( 'largo-follow', array( 'title' => '' ), 'article-bottom');

		// Check
		$widgets = get_option('sidebars_widgets');
		$this->assertEquals('largo-follow-widget-2', $widgets['article-bottom'][0]);

		delete_option('sidebars_widgets');
		update_option('sidebars_widgets', $widgets_backup);
		unset($widgets_backup);
	}
	function test_largo_check_deprecated_widgets() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
	function test_largo_deprecated_footer_widget() {
		// prints a nag
		// uses __
		$this->expectOutputRegex('/[.*]+/'); // This is excessively greedy, it expects any output at all
		largo_deprecated_footer_widget();
	}
	function test_largo_deprecated_sidebar_widget() {
		// prints a nag
		// uses __
		$this->expectOutputRegex('/[.*]+/'); // This is excessively greedy, it expects any output at all
		largo_deprecated_sidebar_widget();
	}
	function test_largo_transition_nav_menus() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}
	function test_largo_update_prominence_term_description_single() {
		$update = array(
			'name' => 'Term 9',
			'description' => 'Term 9 From Outer Space',
			'olddescription' => 'Term Description 9',
			'slug' => 'term-9'
		);
		$term_descriptions = array('Term Description 9');

		$return = largo_update_prominence_term_description_single($update, $term_descriptions);
		$this->assertTrue(is_array($return));

		$term9 = get_term_by( 'slug', 'term-9', 'prominence', 'ARRAY_A' );
		$this->assertEquals('Term 9 From Outer Space', $term9['description']);
	}
	function test_largo_update_prominence_term_descriptions() {

		largo_update_prominence_term_descriptions();
		$terms = get_terms('prominence', array(
			'hide_empty' => false,
			'fields' => 'all'
		));

		$this->markTestIncomplete('This test has not been implemented yet.');
	}
}
