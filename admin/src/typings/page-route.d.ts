declare namespace PageRoute {
  /**
   * the root route key
   * @translate 根路由
   */
  type RootRouteKey = 'root';

  /**
   * the not found route, which catch the invalid route path
   * @translate 未找到路由(捕获无效路径的路由)
   */
  type NotFoundRouteKey = 'not-found';

  /**
   * the route key
   * @translate 页面路由
   */
  type RouteKey =
    | '403'
    | '404'
    | '500'
    | 'constant-page'
    | 'login'
    | 'not-found'
    | 'about'
    | 'card'
    | 'card_details'
    | 'card_list'
    | 'card_memberCardDetails'
    | 'card_type'
    | 'card_type_list'
    | 'chat'
    | 'chat_list'
    | 'chat_message'
    | 'chat_message_list'
    | 'config'
    | 'config_api'
    | 'config_api_manager'
    | 'config_email'
    | 'config_email_black'
    | 'config_email_black_list'
    | 'dashboard'
    | 'dashboard_analysis'
    | 'embedding'
    | 'embedding_file'
    | 'embedding_file_list'
    | 'embedding_list'
    | 'embedding_public'
    | 'embedding_public_list'
    | 'embedding_qa'
    | 'embedding_qa_list'
    | 'exception'
    | 'exception_403'
    | 'exception_404'
    | 'exception_500'
    | 'management'
    | 'management_admin'
    | 'management_admin_member'
    | 'management_operation'
    | 'management_operation_log'
    | 'member'
    | 'member_list'
    | 'prompt'
    | 'prompt_category'
    | 'prompt_category_list'
    | 'prompt_list';

  /**
   * last degree route key, which has the page file
   * @translate 最后一级路由(该级路有对应的页面文件)
   */
  type LastDegreeRouteKey = Extract<
    RouteKey,
    | '403'
    | '404'
    | '500'
    | 'constant-page'
    | 'login'
    | 'not-found'
    | 'about'
    | 'card_details'
    | 'card_list'
    | 'card_memberCardDetails'
    | 'card_type_list'
    | 'chat_list'
    | 'chat_message_list'
    | 'config_api_manager'
    | 'config_email_black_list'
    | 'config'
    | 'dashboard_analysis'
    | 'embedding_file_list'
    | 'embedding_list'
    | 'embedding_public_list'
    | 'embedding_qa_list'
    | 'exception_403'
    | 'exception_404'
    | 'exception_500'
    | 'management_admin_member'
    | 'management_operation_log'
    | 'member_list'
    | 'prompt_category_list'
    | 'prompt_list'
  >;
}
