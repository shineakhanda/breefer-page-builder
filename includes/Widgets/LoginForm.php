<?php
/**
 * Login form widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Login form that turns into a greeting with a logout link once signed in.
 */
final class LoginForm extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'loginform';
	}

	/**
	 * Render.
	 *
	 * @param array<string, mixed> $settings      Settings.
	 * @param string               $children_html Children.
	 * @param string               $node_id       Node id.
	 * @return string
	 */
	public function render( array $settings, string $children_html, string $node_id ): string {
		unset( $children_html, $node_id );

		$redirect = $this->str( $settings, 'redirect' );
		$redirect = '' !== $redirect ? esc_url_raw( $redirect ) : home_url( '/' );

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();

			return '<div class="br-login is-logged-in"><p>'
				. esc_html(
					sprintf(
						/* translators: %s: user display name. */
						__( 'Signed in as %s.', 'breefer' ),
						$user->display_name
					)
				)
				. ' <a href="' . esc_url( wp_logout_url( $redirect ) ) . '">' . esc_html__( 'Log out', 'breefer' ) . '</a></p></div>';
		}

		$form = wp_login_form(
			array(
				'echo'           => false,
				'redirect'       => $redirect,
				'label_log_in'   => $this->str( $settings, 'buttonText', __( 'Log in', 'breefer' ) ),
				'remember'       => true,
				'form_id'        => 'br-loginform',
				'id_username'    => 'br-user-login',
				'id_password'    => 'br-user-pass',
				'id_remember'    => 'br-rememberme',
				'id_submit'      => 'br-submit',
			)
		);

		$lost = $this->bool( $settings, 'hideLost' ) ? '' : '<p class="br-login-links"><a href="' . esc_url( wp_lostpassword_url( $redirect ) ) . '">' . esc_html__( 'Lost your password?', 'breefer' ) . '</a></p>';

		return '<div class="br-login">' . $form . $lost . '</div>';
	}
}
