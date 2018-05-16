<?
namespace Famous\Lib\Utils;
class Constant{
	
	/*MODES START*/
	const DEBUG_MODE = 1;
	const RELEASE_MODE = 2;
	const CURRENT_MODE = 1;
	/*MODES END*/

	/*VARIANTS START*/
	const PREFIX = ""; 		//gsmd or esmd or another
	/*VARIANTS END*/

	/*PATH AND SOURCES START*/
	const CONTROLLERS_PATH = "famous/Controllers/";
	const MODELS_PATH = "famous/Models/";
	const CONTROLLERS_PATH_NS = 'Famous\Controllers';
	const MODELS_PATH_NS = 'Famous\Models';
	const VIEWS_PATH = "famous/Views/";
	const SESSIONS_PATH = "/sessions";		//use it with $_SERVER['DOCUMENT_ROOT']
	const PHANTOM_PATH = "phantom/";
	const PHANTOM_WORKERS_PATH = "phantom/workers/";
	const APPS_PATH = "apps/";

	const CONTROLLER_MAIN = "main";
	const ACTION_INDEX = "index";
	
	const ACTION_PREFIX = "action_";
	const CONTROLLER_PREFIX = "Controller_";
	const MODEL_PREFIX = "Model_";
	/*PATH AND SOURCES END*/

	/*RESPONSE STATUSES START*/
	const ERR_STATUS = "err";
	const OK_STATUS = "ok";
	/*RESPONSE STATUSES END*/

	/*UTILS START*/
	const SESSION_LIFE_TIME = 604800;		//seconds

	const EMAIL_CHECK_TIME = 86400;
	const NEWS_DEMON_TIME = 180;

	const HANG_TIME = 10;

	const LAST_VISIT_AGRESSION_TIME = 86400;
	/*UTILS END*/

	/*SQL TABLES START*/
	const USERS_TABLE = "users";
	const DATA_TABLE = "data";
	const FEEDS_TABLE = "feeds";
	const NEWS_TABLE = "news";
	const TOPS_TABLE = "tops";
	const APPS_TABLE = "apps";
	const ADS_TABLE = "ads";
	const PURCHASES_TABLE = "purchases";
	const SUBSCRIBES_TABLE = "subscribes";
	const ADS_FEED_TABLE = "ads_feed";
	const HONORS_TABLE = "honors";
	const ACHIEVES_TABLE = "achieves";
	const ERRORS_TABLE = "errors";

	const LIKE_TABLE = "like_tasks";
	const LIKE_READY = "like_tasks_ready";
	const LIKE_FROZEN = "like_tasks_frozen";
	const LIKE_INFO = "like_tasks_info";

	const SUBSCRIBE_TABLE = "subscribe_tasks";
	const SUBSCRIBE_READY = "subscribe_tasks_ready";
	const SUBSCRIBE_FROZEN = "subscribe_tasks_frozen";
	const SUBSCRIBE_INFO = "subscribe_tasks_info";

	const BALANCE_TABLE = "balance";
	const DELETES_TABLE = "deletes";

	const NOTIFICATIONS_TABLE = "notifications";
	const NOTIFICATIONS_FEED_TABLE = "notifications_feed";

	const SETTINGS_TABLE = "settings";

	const VERY_USERS_TABLE = "very_users";
	/*SQL TABLES STOP*/

	/*BONUSES START*/
	const START_BONUS = 50;	//50
	/*BONUSES START*/

	/*LIMITS START*/
	const TOP_USERS_LIMIT = 100;
	const NEWS_LIMIT = 10;
	const REPORTS_LIMIT = 200;
	const TOP_LIMIT = 5;

	const LIKE_LIMIT = 40;
	const SUBSCRIBE_LIMIT = 35;
	const FROZEN_LIMIT = 50;

	/*LIMITS END*/

	/*PURCHASES / SUBSCRIBES START*/
	const INIT_PURCHASE_STATUS = 0;		//just come to base
	const INIT_SUBSCRIBE_STATUS = 0;
	const OK_PURCHASE_STATUS = 2;
	const OK_SUBSCRIBE_STATUS = 2;
	const OK_FREE_SUBSCRIBE_STATUS = 4;

	const FINISHED_SUBSCRIBE_STATUS = 3;

	/*PURCHASES / SUBSCRIBES END*/

	/*MARKET ITUNES START*/

	/*MARKET ITUNES END*/

	/*SECURE START*/
	const SECURE_CIPHER = "rijndael-128";
	const SECURE_KEY = "11118611560012835340767891901111";
	const SECURE_MODE = "cbc";
	const SECURE_IV = "5432167342123456";
	/*SECURE END*/

	/*NEWS START*/
	const ONE_TYPE = 1;
	const MULTIPLE_TYPE = 2;
	const DAILY_BONUS = 50;
	const RATE_BONUS = 100;
	/*NEWS END*/

	/*PHANTOM START*/
	const CHECK_FROZEN = 1;
	const CHECK_SWITCH_TYPE = 2;
	const CHECK_PRIVATE = 3;
	/*PHANTOM END*/

	/*QUESTS / TASKS START*/
	const BY_PRIORITY_BALANCE_CHANCE = 5;
	const BY_PRIORITY_CHANCE = 25;
	const BY_PRIORITY_CHANCE_ADMIN = 5;
	const BY_RAND_CHANCE = 65;

	const BY_TIME_DESC_CHANCE = 40;
	const BY_TIME_CHANCE = 10;

	const TRY_GET_QUEST_COUNT = 5;
	const TRY_GET_QUEST_TYPE_COUNT = 3;

	const LIKE_TYPE = 1;
	const SUBSCRIBE_TYPE = 2;

	const CONSUMABLE_TYPE = 1;
	const SUBSCRIPTION_TYPE = 2;

	const LIKE_PRICE_BID = 3;
	const SUBSCRIBE_PRICE_BID = 6;

	const LIKE_PRICE = 1;				//2
	const SUBSCRIBE_PRICE = 1;			//3

	const LIKE_PRICE_MIN = 1;			//1
	const SUBSCRIBE_PRICE_MIN = 1;		//1

	const PRICE_BORDER = 70;		//70%

	const TURBO_GREEN_BONUS = 2;
	const TURBO_BLUE_BONUS = 3;
	const TURBO_RED_BONUS = 4;
	const TURBO_DARK_BONUS = 11;
	const TURBO_FREE_BONUS = 2;

	const CREDIT_PERCENT = 0.8;

	const ACTIVE_TASK_STATUS = 0;
	const READY_TASK_STATUS = 1;
	const FROZEN_TASK_STATUS = 2;
	const HANG_TASK_STATUS = 3;

	const EXPIRE_SUBSCRIBE_TIME = 1209600;		//7 days
	const EXPIRE_LIKE_DELAY = 604800;		//7 days

	const REFRESH_FAST_ANDROID_TIME = 3600;
	const REFRESH_FAST_IOS_TIME = 3600;

	const PARSE_TIME = 600;
	const PARSE_RED_DELAY = 0;
	const PARSE_BLUE_DELAY = 1200;
	const PARSE_GREEN_DELAY = 3000;

	/*QUESTS / TASKS END*/

	/*LVL AND XP START*/
	const LIKE_RATIO = 0.2;
	const SUBSCRIBE_RATIO = 0.2;

	const XP_PER_LVL = 1000;
	const HARD_RANGE = 5;
	const STEP_TASKS_PER_HARD = 60;
	const STEP_BONUS_PER_HARD = 20;
	const LEGEND_LVL_TASK_NUM = 500;
	const LEGEND_LVL_BONUS_SUM = 200;
	/*LVL AND XP END*/

	const PREMIUM_ON = 1;
	const PREMIUM_OFF = 0;

	const APP_VERSION_METEOR = "1.0.0";					//version of apk file in launcher
	const APP_VERSION_PHANTOM = "1.0.0";
	const APP_VERSION_REAL = "1.1.0";					//version of apk file in launcher
	const APP_VERSION_ROYAL = "1.0.0";					//version of apk file in launcher
	const APP_VERSION_REAL_VIP = "1.0.0";					//version of apk file in launcher

	const APP_VERSION_REAL_FLWRS = "1.0.0";					//version of apk file in launcher
	const APP_VERSION_ROYAL_LKS = "1.0.0";
	const APP_VERSION_REAL_LKS = "1.0.0";
	const APP_VERSION_ROYAL_FLWRS = "1.0.0";

	const APP_VERSION_REAL_FOLLOWERS_PREMIUM = "1.0.0";
	const APP_VERSION_ROYAL_LIKES_PREMIUM = "1.0.0";
	const APP_VERSION_FLWRS_BOOST = "1.0.0";

	const APP_VERSION_METEOR_BOOST = "1.0.0";

	const APP_VERSION_ROYAL_FOLLOWERS_TOP = "1.0.0";
	const APP_VERSION_REAL_LIKES_TOP = "1.0.0";

	const SERVICE_VERSION_INSTAGRAM = "1.0.0";

	const PACKAGE_NAME_METEOR = "com.ruboss.meteor";
	const GCM_API_KEY_METEOR = "";

	const PACKAGE_NAME_OLD_REAL = "com.comebackme.gsmd";
	const PACKAGE_NAME_REAL = "com.comebackme.rpvip";
	const PACKAGE_NAME_REAL_VIP = "com.renewal.rmvip";
	const PACKAGE_NAME_REAL_LAUNCHER = "com.comebackme.gsmd";

	const PACKAGE_NAME_REAL_FLWRS = "com.sirbark.synematic";
	const PACKAGE_NAME_ROYAL_LKS = "com.sirbark.fourmatic";
	const PACKAGE_NAME_REAL_LKS = "com.sirbark.liduxe";
	const PACKAGE_NAME_ROYAL_FLWRS = "com.sirbark.dixule";

	const PACKAGE_NAME_METEOR_BOOST = "com.beautybob.starapp";
	const GCM_API_KEY_METEOR_BOOST = "";

	const PACKAGE_NAME_REAL_FOLLOWERS_PREMIUM = "com.nautilius.runpage";
	const PACKAGE_NAME_ROYAL_LIKES_PREMIUM = "com.nautilius.sunrage";
	const PACKAGE_NAME_FLWRS_BOOST = "com.nautilius.boostmage";

	const PACKAGE_NAME_ROYAL_FOLLOWERS_TOP = "com.thundred.okaio";
	const PACKAGE_NAME_REAL_LIKES_TOP = "com.thundred.odaia";
	const PACKAGE_NAME_FREE_FLWRS = "com.thundred.supgimo";

	const GCM_API_KEY_REAL_FOLLOWERS = "";

	const PACKAGE_NAME_ROYAL_LAUCHER = "com.tankbill.sosih";
	const PACKAGE_NAME_REAL_VIP_LAUCHER = "com.moren.balken";

	const PACKAGE_NAME_OLD_ROYAL = "com.renewal.fugs";
	const PACKAGE_NAME_ROYAL = "com.renewal.rfvip";
	const GCM_API_KEY_ROYAL_FOLLOWERS = "";

	const PACKAGE_NAME_METEOR_GP = "com.mambela.meteor";
	const PACKAGE_NAME_PHANTOM = "com.mambela.phantom";

	const PACKAGE_NAME_DONATE_1 = "com.ponago.pideb";

	const PLATFORM_ANDROID = 1;
	const PLATFORM_VERBAL_ANDROID = "android";

	const PLATFORM_IOS = 2;
	const PLATFORM_VERBAL_IOS = "ios";

	const IOS_CER_PASS = "";
	const IOS_ITUNES_SECRET = "";

	const PLATFORM_WINDOWS = 3;
	const PLATFORM_VERBAL_WINDOWS = "windows";

	const PLATFORM_MAC = 4;
	const PLATFORM_VERBAL_MAC = "mac";

	const INST_SERVICE = "instagram";

	const DEFAULT_LANG = "en";

	/*NOTIF START*/
	const NOTIFICATION_ICON = "notif";
	const NOTIFICATION_IMAGES_PATH = "";
	const CAPTCHA_PATH = "captchas/";
	/*NOTIF END*/


	const EXPIRY_LIMIT = 5;
	const WATCH_AD_LIMIT = 5;

	const MAX_META_LENGTH = 70;
	const MAX_HEAD_LENGTH = 70;

	const SIMPLE_DELETE_REASON = 0;
	const PHANTOM_DELETE_REASON = 1;
	const CHECK_DELETE_REASON = 2;
	const READY_DELETE_REASON = 3;

	const REMOVE_LIKE_LIMIT = 10;
	const REMOVE_SUBSCRIBE_LIMIT = 10;
	const REMOVE_QUESTION_LIMIT = 10;
}
