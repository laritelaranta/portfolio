/* jshint esnext: true */
import React from 'react';
import TermsSection from 'promo-template-common/lib/components/TermsSection';
import CloseBtn from 'promo-template-common/lib/components/CloseBtn';

class IntroScreen extends React.Component {

	constructor(props) {
		super(props);
		this.state = {};
	}

	render() {

		var screenContent = this.props.screenContent;

		return (
			<div className="scrollable">
				<CloseBtn />

				<div id="intro-screen">
					<header>
						<div className="title" dangerouslySetInnerHTML={{__html: screenContent.title}}></div>
					</header>
					<section>
						<div className="subTitle" dangerouslySetInnerHTML={{__html: screenContent.subTitle}}></div>
						<div className="btn-Wrap">
							<button className="btn-white" onClick={this.props.revealScreenSelected}>Reveal</button>
						</div>
					</section>
					<footer>
						<TermsSection termsLabel={screenContent.termsLabel}/>
					</footer>
				</div>
			</div>
		);
	}

}

export default IntroScreen;