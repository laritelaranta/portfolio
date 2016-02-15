/* jshint esnext: true */
import React from 'react';
import TermsSection from 'promo-template-common/lib/components/TermsSection';
import CloseBtn from 'promo-template-common/lib/components/CloseBtn';

class DeclineScreen extends React.Component {

	constructor(props) {
		super(props);
		this.state = {};
	}

	render() {
		var screenContent = this.props.screenContent;

		return (
			<div className="scrollable">
				<CloseBtn />

				<div id="decline-screen">
					<header>
						<div className="title" dangerouslySetInnerHTML={{__html: screenContent.title}}></div>
					</header>
					<section>
						<div className="subTitle" dangerouslySetInnerHTML={{__html: screenContent.subTitle}}></div>
						<div className="mainContentWrap">
							<div className="mainContent" dangerouslySetInnerHTML={{__html: screenContent.mainContent}}></div>
							<button className="btn-white" onClick={this.props.onHomeSelected}>Back to games</button>
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

export default DeclineScreen;