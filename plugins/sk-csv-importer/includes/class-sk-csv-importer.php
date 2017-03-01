<?php
/**
 * SK_CSV_Importer
 * ===============
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_CSV_Importer
 */

class SK_CSV_Importer extends WP_Importer {

	/**
	 * The current file id.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The current file url.
	 *
	 * @var string
	 */
	public $file_url;

	/**
	 * The current import page.
	 *
	 * @var string
	 */
	public $import_page;

	/**
	 * The current delimiter.
	 *
	 * @var string
	 */
	public $delimiter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->import_page = 'woocommerce_tax_rate_csv';
		$this->delimiter   = empty( $_POST[ 'delimiter' ] ) ? ',' : (string) wc_clean( $_POST[ 'delimiter' ] );
	}

	/**
	 * Registered callback function for the WordPress Importer.
	 *
	 * Manages the three separate stages of the CSV import process.
	 */
	public function dispatch() {
		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];

		switch ( $step ) {
			case 0:
				$this->greet();
			break;

			case 1:
				check_admin_referer( 'import-upload' );

				if ( $this->handle_upload() ) {

					if ( $this->id ) {
						$file = get_attached_file( $this->id );
					} else {
					$file = ABSPATH . $this->file_url;
				}

				add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

				$this->import( $file );
			}
			break;
		}

		$this->footer();
	}

	/**
	* Import is starting.
	*/
	private function import_start() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}

		wc_set_time_limit( 0 );
		@ob_flush();
		@flush();
		@ini_set( 'auto_detect_line_endings', '1' );
	}

	/**
	* UTF-8 encode the data if `$enc` value isn't UTF-8.
	*
	* @param mixed $data
	* @param string $enc
	* @return string
	*/
	public function format_data_from_csv( $data, $enc ) {
		return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
	}

	/**
	* Import the file if it exists and is valid.
	*
	* @param mixed $file
	*/
	public function import( $file ) {
		if ( ! is_file( $file ) ) {
			$this->import_error( __( 'Filen existerar inte, var vänlig försök igen.', 'sk-csvimporter' ) );
		}

		$this->import_start();

		// Count every loop iteration.
		$loop = 0;

		// Count every successful product import.
		$added_product = 0;

		if ( ( $handle = fopen( $file, "r" ) ) !== false ) {
			$header = fgetcsv( $handle, 0, $this->delimiter );

			// Get default DeDU fields.
			$dedu_pf = SK_DeDU_Product_Fields::get_instance()->get_default_fields();

			// Make sure we have the right number of columns.
			if ( 11 === sizeof( $header ) ) {
				while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== false ) {
					list(
						$post_title,
						$post_content,
						$sku,
						$price,
						$price_unit,
						$yrke_id,
						$arendetyp_id,
						$kategori_id,
						$underkategori_id,
						$prioritet_id,
						$product_owner ) = $row;

					// Skip every row that don't have a SKU.
					if ( ! empty( $sku ) ) {
						$args = array(
							'post_title'	=> $post_title,
							'post_content'	=> $post_content,
							'post_type'		=> 'product',
						);
						if ( ( $post_id = wp_insert_post( $args ) ) !== 0 ) {
							// Add SKU.
							$sku = ( empty( $sku ) ) ? SKW()->generate_sku( $post_id ) : $sku;
							update_post_meta( $post_id, '_sku', $sku );

							// Add price.
							update_post_meta( $post_id, '_regular_price', $price );
							update_post_meta( $post_id, '_price', $price );

							// Try to get the taxonomy term for
							// this unit type.
							if ( $term = get_term_by( 'slug', sanitize_title( $price_unit ), 'product_unit_type' ) ) {
								wp_set_object_terms( $post_id, $term->term_id, 'product_unit_type' );
							}

							// Add DeDU fields.
							$dedu_fields = array(
								'YrkeId'			=> ( ! empty( $yrke_id ) ) ? $yrke_id : $dedu_pf[ 'YrkeId' ],
								'ArendetypId'		=> ( ! empty( $arendetyp_id ) ) ? $arendetyp_id : $dedu_pf[ 'ArendetypId' ],
								'KategoriId'		=> ( ! empty( $kategori_id ) ) ? $kategori_id : $dedu_pf[ 'KategoriId' ],
								'UnderkategoriId'	=> ( ! empty( $underkategori_id ) ) ? $underkategori_id : $dedu_pf[ 'UnderkategoriId' ],
								'PrioritetId'		=> ( ! empty( $PrioritetId ) ) ? $prioritet_id : $dedu_pf[ 'PrioritetId' ],
							);
							update_post_meta( $post_id, 'sk_dedu_fields', $dedu_fields );

							// Add product owner if we found one.
							if ( $product_owner = skios_get_product_owner_by_label( $product_owner ) ) {
								update_post_meta( $post_id, '_product_owner', $product_owner[ 'id' ] );
							}

							// Count up.
							$added_product++;
						}
					}

					// Count up loop.
					$loop++;
				}
			} else {
				$this->import_error( __( 'CSV filen är felformaterad.', 'sk-csvimporter' ) );
			}

			fclose( $handle );
		}

		// Show Result
		echo '<div class="updated settings-error"><p>
		' . sprintf( __( 'Import klar - importerade <strong>%s</strong> produkter.', 'sk-csvimporter' ), $added_product ) . '
		</p></div>';

		$this->import_end();
	}

	/**
	* Performs post-import cleanup of files and the cache.
	*/
	public function import_end() {
		echo '<p>' . __( 'Allt klart!', 'sk-csvimporter' ) . '</p>';

		do_action( 'import_end' );
	}

	/**
	* Handles the CSV upload and initial parsing of the file to prepare for.
	* displaying author import options.
	*
	* @return bool False if error uploading or invalid file, true otherwise
	*/
	public function handle_upload() {
		if ( empty( $_POST['file_url'] ) ) {
			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
			$this->import_error( $file['error'] );
			}

			$this->id = absint( $file[ 'id' ] );
		} elseif ( file_exists( ABSPATH . $_POST[ 'file_url' ] ) ) {
			$this->file_url = esc_attr( $_POST[ 'file_url' ] );
		} else {
			$this->import_error();
		}

		return true;
	}

	/**
	* Output header html.
	*/
	public function header() {
		echo '<div class="wrap"><div class="icon32 icon32-woocommerce-importer" id="icon-woocommerce"><br></div>';
		echo '<h1>' . __( 'Importera produkter', 'sk-csvimporter' ) . '</h1>';
	}

	/**
	* Output footer html.
	*/
	public function footer() {
		echo '</div>';
	}

	/**
	 * Output information about the uploading process.
	 */
	public function greet() {
		echo '<div class="narrow">';
		echo '<p>' . __( 'Hejsan! Ladda upp en CSV fil innehållandes produkter för att importera till din e-butik. Välj en .csv fil att ladda upp, klicka sedan på "Ladda upp fil och importera".', 'sk-csvimporter' ).'</p>';

		echo '<p>' . sprintf( __( 'Produkter måste vara definerade med kolumner i en specifik ordning (8 kolumner). <a href="%s">Klicka här för att ladda ner ett exempel</a>.', 'sk-csvimporter' ), SK_CVS_IMPORTER_PLUGIN_URL . '/dummy-data/sample_products.csv' ) . '</p>';

		$action = 'admin.php?import=sk_product_import_csv&step=1';

		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?><div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'woocommerce' ); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
		else :
			?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="upload"><?php _e( 'Choose a file from your computer:', 'woocommerce' ); ?></label>
							</th>
							<td>
								<input type="file" id="upload" name="import" size="25" />
								<input type="hidden" name="action" value="save" />
								<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
								<small><?php printf( __('Maximum size: %s', 'woocommerce' ), $size ); ?></small>
							</td>
						</tr>
						<tr>
							<th>
								<label for="file_url"><?php _e( 'OR enter path to file:', 'woocommerce' ); ?></label>
							</th>
							<td>
								<?php echo ' ' . ABSPATH . ' '; ?><input type="text" id="file_url" name="file_url" size="25" />
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'Delimiter', 'woocommerce' ); ?></label><br/></th>
							<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" class="button" value="<?php esc_attr_e( 'Ladda upp fil och importera', 'sk-csvimporter' ); ?>" />
				</p>
			</form>
			<?php
		endif;

		echo '</div>';
	}

	/**
	* Show import error and quit.
	* @param  string $message
	*/
	private function import_error( $message = '' ) {
		echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
		if ( $message ) {
		echo esc_html( $message );
		}
		echo '</p>';
		$this->footer();
		die();
	}

	/**
	* Added to http_request_timeout filter to force timeout at 60 seconds during import.
	*
	* @param  int $val
	* @return int 60
	*/
	public function bump_request_timeout( $val ) {
		return 60;
	}

}