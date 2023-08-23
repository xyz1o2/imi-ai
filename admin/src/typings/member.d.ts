declare namespace Member {
  interface Member {
    /** 用户id */
    id: number;
    /** 用户id */
    recordId: string;
    /** 邮箱 */
    email: string;
    /** 昵称 */
    nickname: string;
    password: string;
    /**
     * 用户状态
     * - 1: 启用
     * - 2: 禁用
     */
    status: number;
    statusText: string;
    registerTime: number;
    registerIp: string;
    lastLoginTime: number;
    lastLoginIp: string;
  }

  interface MemberListResponse extends Api.BaseResponse {
    list: Member[];
  }
}
