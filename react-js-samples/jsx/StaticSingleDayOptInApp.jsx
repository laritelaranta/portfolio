/* jshint esnext: true */
/* globals $, unicornWhisperer */

import React from 'react';
import BaseApp from 'promo-template-common/lib/components/BaseApp';
import BaseAppStates from 'promo-template-common/lib/constants/AppStates';
import OptInStates from './constants/OptInStates';
import PromoStore from 'promo-template-common/lib/stores/PromoStore';
import PromoActions from 'promo-template-common/lib/actions/PromoActions';
import PreloadScreen from 'promo-template-common/lib/components/PreloadScreen';
import StaticScreen from './components/venture/jackpotjoy/StaticScreen';
import Utility from 'promo-template-common/lib/utils/Utility';

class StaticSingleDayOptInApp extends React.Component {

	constructor(props) {
		super(props);

		this.state = PromoStore.getState();

		this._onChangeState = this._onChangeState.bind(this);
		this._startUpPromo = this._startUpPromo.bind(this);
		this._processPreviousRounds = this._processPreviousRounds.bind(this);
		this._optInHandler = this._optInHandler.bind(this);
		this._viewGamesHandler = this._viewGamesHandler.bind(this);
	}

	// Listen for changes from PromoStore
	componentWillMount() {
		this.setState({isShowingPreloader: true});
		PromoStore.addChangeListener(this._onChangeState);
	}

	// Remove listener on PromoStore
	componentWillUnmount() {
		PromoStore.removeChangeListener(this._onChangeState);
	}

	// Set state when received change from PromoStore
	_onChangeState() {

		this.setState(PromoStore.getState());

		// check promo is ready and has not been previous started
		if (this.state.baseState === BaseAppStates.PROMO_READY && this.state.promoHasStarted === undefined) {
			this._startUpPromo();
		}

		// check whether to hide preloader
		switch (this.state.baseState) {
			case BaseAppStates.MEMBER_NOT_VALID:
			case BaseAppStates.MEMBER_NOT_ELIGIBLE:
			case BaseAppStates.PROMO_EXPIRED:
			case BaseAppStates.PROMO_ERROR:
				this.setState({isShowingPreloader: false});
				break;
		}
	}

	// Start her up
	_startUpPromo() {
		this.setState({promoHasStarted: true}); // set promoHasStarted var so this method doesn't get called again

		// check if we have any previous round data
		if (this.state.previousRounds !== null && this.state.previousRounds.length > 0) {
			this._processPreviousRounds();
		} else { // otherwise lets preload images
			this.setState({
				currentScreen: OptInStates.STATIC_SCREEN,
				hasMemberOptedIn: false,
				isShowingPreloader: false
			});
		}
	}

	// format previous round and set vars
	_processPreviousRounds() {
		var rounds = this.state.previousRounds;
		var hasMemberOptedIn = false;

		for (var i = 0; i < rounds.length; i++) {
			if (rounds[i].selection == 'OPTED IN') {
				hasMemberOptedIn = true;
			}
		}

		//console.log('hasMemberOptedIn: '+hasMemberOptedIn);

		if (hasMemberOptedIn) {
			this.setState({
				currentScreen: OptInStates.STATIC_SCREEN,
				hasMemberOptedIn: true,
				isShowingPreloader: false
			});

		} else { // if has previous Round, but not opted in
			this.setState({
				currentScreen: OptInStates.STATIC_SCREEN,
				hasMemberOptedIn: false,
				isShowingPreloader: false
			});
		}
	}

	// submit to the rounds table
	_optInHandler() {
		var selectRoundId = 1;
		var userAgentId = 100;
		var selection = 'OPTED IN';
		var jsonRounds = '{"rou": [{"rId": "' + selectRoundId + '", "selection": "' + selection + '"}, {"rId": "' + userAgentId + '", "selection": "' + Utility.getUserAgent() + '"}]}';

		this.setState({hasMemberOptedIn: true});
		PromoActions.submitRound(jsonRounds);
	}

	_viewGamesHandler() {
		var url = this.state.content.staticScreen.playURL.toString();
		unicornWhisperer.goToPage(url);
	}

	// React JS function to render components to screen
	render() {
		var preloader = <PreloadScreen />;
		var partial;

		switch (this.state.baseState) {

			case BaseAppStates.PROMO_READY:

				switch (this.state.currentScreen) {

					case OptInStates.STATIC_SCREEN:
						partial = <StaticScreen
							screenContent={this.state.content.staticScreen}
							hasOptedIn={true}
							optInCallback={this._optInHandler}
							viewGamesCallback={this._viewGamesHandler}
							/>;
						break;
				}

				break;

			default:
				partial = <BaseApp
					baseState={this.state.baseState}
					onHomeSelected={this._homeSelectedHandler}
					content={this.state.content}
					/>;
				break;
		}

		return (
			<div>
				{ partial }
				{ (this.state.isShowingPreloader === true ) ? preloader : null  }
			</div>
		);
	}
}

export default StaticSingleDayOptInApp;
