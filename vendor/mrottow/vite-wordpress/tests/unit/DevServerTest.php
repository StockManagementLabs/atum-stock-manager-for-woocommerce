<?php declare( strict_types=1 );

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use ViteWordPress\DevServer;

class DevServerTest extends TestCase {
	private DevServer $dev_server;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Monkey\Functions\expect( 'get_site_url' )->andReturn( 'https://example.com' );

		$this->dev_server = new DevServer();
		$this->dev_server->set_config([
			'base' => 'wp-content/plugins/create-vite-block',
			'outDir' => 'build',
		]);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testConstructorSetsDefaults(): void {
		$this->assertEquals( 'https://example.com', $this->dev_server->get_server_host() );
		$this->assertEquals( '5173', $this->dev_server->get_server_port() );
	}

	public function testSetServerHost(): void {
		$this->dev_server->set_server_host( 'https://new-server.com' );

		$this->assertEquals( 'https://new-server.com', $this->dev_server->get_server_host() );
	}

	public function testSetServerPort(): void {
		$this->dev_server->set_server_port( 1234 );

		$this->assertEquals( '1234', $this->dev_server->get_server_port() );
	}


	public function testIsClientActive(): void {
		$dev_server = $this->getMockBuilder( DevServer::class )
		                  ->onlyMethods( [ 'vite_server_request', 'get_client_url' ] )
		                  ->getMock();

		$dev_server->method( 'vite_server_request' )->willReturn( [
			'errors'   => null,
			'response' => 200,
		] );

		$this->assertTrue( $dev_server->is_client_active() );
	}

	public function testIsConfigActive(): void {
		$dev_server = $this->getMockBuilder( DevServer::class )
		                  ->onlyMethods( [ 'vite_server_request', 'get_config_url' ] )
		                  ->getMock();

		$dev_server->method( 'vite_server_request' )->willReturn( [
			'errors'   => null,
			'response' => 200,
			'data'     => [ 'base' => '/vite/' ],
		] );

		$this->assertTrue( $dev_server->is_config_active() );
		$this->assertEquals( [ 'base' => '/vite/' ], $dev_server->get_config() );
	}

	public function testGetRelativeLocalPath()
	{
		$from = '/absolute/path/to/my/folder/';
		$to = '/absolute/path/to/my/folder/file.php';

		$result = $this->dev_server->get_relative_local_path($from, $to);

		$this->assertEquals('file:./file.php', $result);
	}

	public function testGetRelativeLocalPathWithNestedDir()
	{
		$from = '/absolute/path/to/my/folder/';
		$to = '/absolute/path/to/my/folder/nested/file.php';

		$result = $this->dev_server->get_relative_local_path($from, $to);

		$this->assertEquals('file:./nested/file.php', $result);
	}

	public function testGetRelativeLocalPathParentDir()
	{
		$from = '/absolute/path/to/my/folder/';
		$to = '/absolute/path/to/my/file.php';

		$result = $this->dev_server->get_relative_local_path($from, $to);

		$this->assertEquals('file:./../file.php', $result);
	}

	public function testGetRelativeLocalPathWithDifferentBase()
	{
		$from = '/absolute/path/to/my/folder/';
		$to = '/absolute/path/to/another/place/file.php';

		$result = $this->dev_server->get_relative_local_path($from, $to);

		$this->assertEquals('file:./../../another/place/file.php', $result);
	}

	public function testGetFileName()
	{
		$path = 'wp-content/plugins/create-vite-block/build/js/app.js';
		$result = $this->dev_server->get_file_name($path);

		$this->assertEquals('js/app.js', $result);
	}

	public function testGetFileNameWithQueryString()
	{
		$path = 'wp-content/plugins/create-vite-block/build/js/app.js?version=123&something=else';
		$result = $this->dev_server->get_file_name($path);

		$this->assertEquals('js/app.js', $result);
	}

	public function testGetFileNameWithoutBaseAndOutDir()
	{
		$path = 'random/path/js/app.js';
		$result = $this->dev_server->get_file_name($path);

		$this->assertFalse($result);
	}

	public function testGetFileNameWithEmptyPath()
	{
		$path = '';
		$result = $this->dev_server->get_file_name($path);

		$this->assertFalse($result);
	}
}
