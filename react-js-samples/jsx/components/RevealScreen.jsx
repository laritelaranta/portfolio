/* jshint esnext: true */
// TaskListScreen.jsx
import React from 'react';
import TermsSection from 'promo-template-common/lib/components/TermsSection';
import CloseBtn from 'promo-template-common/lib/components/CloseBtn';

class RevealScreen extends React.Component {

	constructor(props) {
		super(props);
		this.state = {};
	}

	render() {
		var screenContent = this.props.screenContent;
		var OptInData = this.props.optInData;
		var currentDayIndex = this.props.currentDayIndex;
		var dynamicBody = screenContent.mainContent.toString().replace(/{percentage}/g, OptInData[currentDayIndex - 1]);

		return (
			<div className="scrollable">
				<CloseBtn />

				<div id="reveal-screen">
					<header>
						<div className="title" dangerouslySetInnerHTML={{__html: screenContent.title}}></div>
					</header>
					<section>
						<div className="subTitle" dangerouslySetInnerHTML={{__html: screenContent.subTitle}}></div>
						<div className="mainContentWrap">
							<div className="mainContent" dangerouslySetInnerHTML={{__html: dynamicBody}}></div>
							<div className="btn-wrap">
								<button className="btn-white" onClick={this.props.onAcceptSelected}>YES</button>
								<button className="btn-white" onClick={this.props.onDeclineSelected}>NO</button>
							</div>
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

export default RevealScreen;