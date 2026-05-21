<?php declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use ViteWordPress\ManifestResolver;

final class ManifestResolverTest extends TestCase {

	private string $manifest_json_path;
	private string $manifest_php_path;
	private array $resolved_manifest;

	protected function setUp(): void {
		$this->resolved_manifest = [
			'src/main.js'   => [
				'file'    => 'assets/main.123456.js',
				'css'     => [ 'assets/main.123456.css' ],
				'imports' => [ 'src/vendor.js' ]
			],
			'src/vendor.js' => [
				'file' => 'assets/vendor.123456.js'
			],
			'source/component.js' => [
				'file' => 'assets/component.123456.js'
			]
		];

		$this->manifest_json_path = sys_get_temp_dir() . '/manifest.json';
		file_put_contents( $this->manifest_json_path, json_encode( $this->resolved_manifest ) );

		$this->manifest_php_path = sys_get_temp_dir() . '/manifest.php';
		file_put_contents( $this->manifest_php_path, '<?php return ' . var_export( $this->resolved_manifest, true ) . ';' );
	}

	protected function tearDown(): void {
		// Remove temporary files
		@unlink( $this->manifest_json_path );
		@unlink( $this->manifest_php_path );
	}

	public function testSetManifestAndResolveJson(): void {
		$resolver = new ManifestResolver();
		$resolver->set_manifest( $this->manifest_json_path );

		$manifest = $resolver->get();

		$this->assertSame( $this->resolved_manifest, $manifest );
	}

	public function testSetManifestAndResolvePhp(): void {
		$resolver = new ManifestResolver();
		$resolver->set_manifest( $this->manifest_php_path );

		$manifest = $resolver->get();
		$this->assertSame( $this->resolved_manifest, $manifest );
	}

	public function testSetSrcAndHasEntry(): void {
		$resolver = new ManifestResolver();
		$resolver->set_manifest( $this->manifest_json_path );
		$resolver->set_src( 'src' );

		$this->assertTrue( $resolver->has( 'main.js' ) );
		$this->assertFalse( $resolver->has( 'nonexistent.js' ) );
	}

	public function testAlternateSrc(): void {
		$resolver = new ManifestResolver();
		$resolver->set_manifest( $this->manifest_json_path );
		$resolver->set_src( 'source' );

		$main_entry = $resolver->get( 'main.js' );
		$component_entry = $resolver->get( 'component.js' );

		$this->assertNotSame( $this->resolved_manifest['src/main.js'], $main_entry );
		$this->assertSame( $this->resolved_manifest['source/component.js'], $component_entry );
	}

	public function testGetEntry(): void {
		$resolver = new ManifestResolver();
		$resolver->set_manifest( $this->manifest_json_path );
		$resolver->set_src( 'src' );

		$entry = $resolver->get( 'main.js' );
		$this->assertSame( $this->resolved_manifest['src/main.js'], $entry );
	}

	public function testGetByFile(): void {
		$resolver = new ManifestResolver();
		$resolver->set_manifest( $this->manifest_json_path );

		$entry = $resolver->get_by_file( 'assets/main.123456.js' );
		$this->assertSame( $this->resolved_manifest['src/main.js'], $entry );

		$this->assertFalse( $resolver->get_by_file( 'nonexistent.js' ) );
	}

	public function testMissingManifestThrowsException(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'ViteWordpress: Manifest has not been set yet.' );

		$resolver = new ManifestResolver();
		$resolver->get();
	}

	public function testNonExistentManifestFileThrowsException(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'ViteWordpress: Manifest file path does not exist. Try to run `yarn build` or `npm run build` first.' );

		$resolver = new ManifestResolver();
		$resolver->set_manifest( '/path/to/nonexistent/manifest.json' );
	}

	public function testUnknownManifestFileTypeThrowsException(): void {
		$unknownFilePath = sys_get_temp_dir() . '/manifest.txt';
		file_put_contents( $unknownFilePath, 'unknown' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'ViteWordpress: Unknown manifest file type.' );

		$resolver = new ManifestResolver();
		$resolver->set_manifest( $unknownFilePath );

		@unlink( $unknownFilePath );
	}
}
