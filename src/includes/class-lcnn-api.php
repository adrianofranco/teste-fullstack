<?php
/**
 * Gerencia a comunicação com a API
 *
 * @package LCNN
 */


class LCNN_API extends LCNN_Base {


	/**
	 * URL API de loterias.
	 *
	 * @var string
	 */
	private $base_url = 'https://loteriascaixa-api.herokuapp.com/api';


	/**
	 * Obter dados de um concurso específico da loteria.
	 *
	 * @param string $loteria Identificador de qual loteria.
	 * @param string $concurso Número do concurso.
	 *
	 * @return string|array Os dados do concurso em JSON ou mensagem de erro.
	 */
	public function get_data(string $loteria, string $concurso ) : array|string {
		$cache_key = "concurso_{$concurso}_da_{$loteria}";

		$is_cached = wp_cache_get( $cache_key );

		// Se estiver no cache, processa a obtenção dos dados.
		if ( ! $is_cached ) :

			// Verifica se há concurso já armazenado.
			$is_stored = $this->is_value_stored( $loteria, $concurso );

			// Se houcver armazenamento, retorna os dados do post. Caso não usa o recurso da API.
			if ( $is_stored->have_posts() ) :

				while ( $is_stored->have_posts() ) :
					$is_stored->the_post();
					$result = get_the_content();
					wp_cache_set( $cache_key, $result, '', 3600 );
				endwhile;
			else :

				$url = $this->base_url . "/$loteria/$concurso"; // Constrói a URL para a API da loteria.

				$response = wp_remote_get( $url );

				if ( is_wp_error( $response ) ) :
					return __( 'Error: Não foi possivel acessar a API', 'loteria' );
				endif;

				$body = wp_remote_retrieve_body( $response );

				$data = json_decode( $body, true );

				if ( ! empty( $data['concurso'] ) ) :
					$concurso_numero = $data['concurso'];

					wp_insert_post(
						array(
							'post_title'   => "Concurso numero: $concurso_numero da $loteria",
							'post_content' => $body,
							'post_status'  => 'publish',
							'post_type'    => 'loterias',
							'meta_input'   => array(
								'loteria'  => $loteria,
								'concurso' => $concurso_numero,
							),
						)
					);
				else :
					return __( 'Error: Não houve retorno da loteria', 'loteria' );
				endif;
				$result = $body;
				wp_cache_set( $cache_key, $result, '', 3600 );
			endif;
		else :
			$result = $is_cached;
		endif;

		return json_decode( $result, true );
	}//end get_data()
}//end class
