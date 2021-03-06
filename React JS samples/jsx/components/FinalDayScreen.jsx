/* jshint esnext: true */
import React from 'react';
import TermsSection from 'promo-template-common/lib/components/TermsSection';
import CloseBtn from 'promo-template-common/lib/components/CloseBtn';

class FinalDayScreen extends React.Component {

	constructor(props) {
		super(props);
		this.state = {};
	}

	render() {
		var finalDay = this.props.currentDayIndex;
		var screenContent = this.props.screenContent;
		var optInData = this.props.optInData;
		var dynamicBody = screenContent.mainContent.toString().replace(/{percentage}/g, optInData[finalDay - 1]);

		return (
			<div className="scrollable">
				<CloseBtn />

				<div id="accept-screen">
					<header>
						<div className="title" dangerouslySetInnerHTML={{__html: screenContent.title}}></div>
					</header>
					<section>
						<div className="subTitle" dangerouslySetInnerHTML={{__html: screenContent.subTitle}}></div>
						<div className="mainContentWrap">
							<div className="mainContent" dangerouslySetInnerHTML={{__html: dynamicBody}}></div>
							<button className="btn-white" onClick={this.props.onHomeSelected}>Play</button>
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

export default FinalDayScreen;