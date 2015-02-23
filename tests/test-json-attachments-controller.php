<?php

/**
 * Unit tests covering WP_JSON_Attachments_Controller functionality
 *
 * @package WordPress
 * @subpackage JSON API
 */
class WP_Test_JSON_Attachments_Controller extends WP_Test_JSON_Post_Type_Controller_Testcase {

	public function setUp() {
		parent::setUp();

		$this->editor_id = $this->factory->user->create( array(
			'role' => 'editor',
		) );
		$this->author_id = $this->factory->user->create( array(
			'role' => 'author',
		) );
		$this->contributor_id = $this->factory->user->create( array(
			'role' => 'contributor',
		) );

		$orig_file = DIR_TESTDATA . '/images/canola.jpg';
		$this->test_file = '/tmp/canola.jpg';
		@copy( $orig_file, $this->test_file );

	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wp/media', $routes );
		$this->assertCount( 2, $routes['/wp/media'] );
		$this->assertArrayHasKey( '/wp/media/(?P<id>[\d]+)', $routes );
		$this->assertCount( 3, $routes['/wp/media/(?P<id>[\d]+)'] );
	}

	public function test_get_items() {
		
	}

	public function test_get_item() {
		$attachment_id = $this->factory->attachment->create_object( $this->test_file, 0, array(
			'post_mime_type' => 'image/jpeg',
			'post_excerpt'   => 'A sample caption',
		) );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Sample alt text' );
		$request = new WP_JSON_Request( 'GET', '/wp/media/' . $attachment_id );
		$response = $this->server->dispatch( $request );
		$this->check_get_post_response( $response );
	}

	public function test_create_item() {
		wp_set_current_user( $this->author_id );
		$request = new WP_JSON_Request( 'POST', '/wp/media' );
		$request->set_header( 'Content-Type', 'image/jpeg' );
		$request->set_header( 'Content-Disposition', 'filename=canola.jpg' );
		$request->set_body( file_get_contents( $this->test_file ) );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = json_ensure_response( $response );
		$this->assertEquals( 201, $response->get_status() );
	}

	public function test_create_item_empty_body() {
		wp_set_current_user( $this->author_id );
		$request = new WP_JSON_Request( 'POST', '/wp/media' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'json_upload_no_data', $response, 400 );
	}

	public function test_create_item_missing_content_type() {
		wp_set_current_user( $this->author_id );
		$request = new WP_JSON_Request( 'POST', '/wp/media' );
		$request->set_body( file_get_contents( $this->test_file ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'json_upload_no_content_type', $response, 400 );
	}

	public function test_create_item_missing_content_disposition() {
		wp_set_current_user( $this->author_id );
		$request = new WP_JSON_Request( 'POST', '/wp/media' );
		$request->set_header( 'Content-Type', 'image/jpeg' );
		$request->set_body( file_get_contents( $this->test_file ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'json_upload_no_content_disposition', $response, 400 );
	}

	public function test_create_item_bad_md5_header() {
		wp_set_current_user( $this->author_id );
		$request = new WP_JSON_Request( 'POST', '/wp/media' );
		$request->set_header( 'Content-Type', 'image/jpeg' );
		$request->set_header( 'Content-Disposition', 'filename=canola.jpg' );
		$request->set_header( 'Content-MD5', 'abc123' );
		$request->set_body( file_get_contents( $this->test_file ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'json_upload_hash_mismatch', $response, 412 );
	}

	public function test_create_item_invalid_upload_files_capability() {
		wp_set_current_user( $this->contributor_id );
		$request = new WP_JSON_Request( 'POST', '/wp/media' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'json_cannot_create', $response, 400 );
	}

	public function test_create_item_invalid_edit_permissions() {
		$post_id = $this->factory->post->create( array( 'post_author' => $this->editor_id ) );
		wp_set_current_user( $this->author_id );
		$request = new WP_JSON_Request( 'POST', '/wp/media' );
		$request->set_param( 'post_id', $post_id );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'json_cannot_edit', $response, 401 );
	}

	public function test_update_item() {
		
	}

	public function test_delete_item() {
		
	}

	public function test_prepare_item() {
		
	}

	public function test_get_item_schema() {
		$request = new WP_JSON_Request( 'GET', '/wp/media/schema' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['properties'];
		$this->assertEquals( 18, count( $properties ) );
		$this->assertArrayHasKey( 'author', $properties );
		$this->assertArrayHasKey( 'alt_text', $properties );
		$this->assertArrayHasKey( 'caption', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'comment_status', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'guid', $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'media_type', $properties );
		$this->assertArrayHasKey( 'media_details', $properties );
		$this->assertArrayHasKey( 'modified', $properties );
		$this->assertArrayHasKey( 'post_id', $properties );
		$this->assertArrayHasKey( 'ping_status', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'source_url', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'type', $properties );
	}

	public function tearDown() {
		parent::tearDown();
		if ( file_exists( $this->test_file ) ) {
			unlink( $this->test_file );
		}
	}

	protected function check_get_post_response( $response, $context = 'view' ) {
		parent::check_get_post_response( $response, $context );

		$data = $response->get_data();
		$attachment = get_post( $data['id'] );

		$this->assertEquals( get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ), $data['alt_text'] );
		$this->assertEquals( $attachment->post_content, $data['caption'] );
		$this->assertEquals( $attachment->post_excerpt, $data['description'] );

		if ( $attachment->post_parent ) {
			$this->assertEquals( $attachment->post_parent, $data['post_id'] );
		} else {
			$this->assertNull( $data['post_id'] );
		}

		$this->assertEquals( wp_get_attachment_url( $attachment->ID ), $data['source_url']  );

	}

}
