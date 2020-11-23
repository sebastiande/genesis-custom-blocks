/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { SvgContainer } from '../components';

/**
 * The Inbox icon.
 *
 * @return {React.ReactElement} The Inbox icon.
 */
const Inbox = () => (
	<SvgContainer>
		<path fill="none" d="M0 0h24v24H0V0z" />
		<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5v-3h3.56c.69 1.19 1.97 2 3.45 2s2.75-.81 3.45-2H19v3zm0-5h-4.99c0 1.1-.9 2-2 2s-2-.9-2-2H5V5h14v9z" />
	</SvgContainer>
);

export default Inbox;
