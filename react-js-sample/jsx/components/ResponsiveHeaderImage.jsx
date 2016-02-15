/* jshint esnext: true */
'use strict';

import React from 'react';

class ResponsiveHeaderImage extends React.Component {

	constructor(props) {
		super(props);
		this.state = {};
	}

	render() {

		var sourceHTML = React.renderToStaticMarkup(
			<picture className='promotions-img'>
				<source
					srcSet={'images/header/scale-1/' + this.props.imageName + '-00-972.png, images/header/scale-2/' + this.props.imageName + '-00-972.png'}
					media='(min-width: 972px)'/>
				<source
					srcSet={'images/header/scale-1/' + this.props.imageName + '-00-684.png, images/header/scale-2/' + this.props.imageName + '-00-684.png'}
					media='(min-width: 684px)'/>
				<source
					srcSet={'images/header/scale-1/' + this.props.imageName + '-00-588.png, images/header/scale-2/' + this.props.imageName + '-00-588.png'}
					media='(min-width: 588px)'/>
				<source
					srcSet={'images/header/scale-1/' + this.props.imageName + '-00-444.png, images/header/scale-2/' + this.props.imageName + '-00-444.png'}
					media='(min-width: 444px)'/>
				<img
					srcSet={'images/header/scale-1/' + this.props.imageName + '-00-300.png, images/header/scale-2/' + this.props.imageName + '-00-300.png'}
					alt='Safari Cashback'/>
			</picture>
		);

		return (
			<div className='responsive-img-holder' dangerouslySetInnerHTML={{__html: sourceHTML}}></div>
		);
	}
}

export default ResponsiveHeaderImage;