import { NavLink, Outlet } from "react-router-dom";

export default function AppLayout() {
  const getNavClass = ({ isActive }) =>
    isActive ? "nav-link nav-link-active" : "nav-link";

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="sidebar-brand">
          <div className="brand-title">Clinic App</div>
          <div className="brand-subtitle">Quản lý phòng khám</div>
        </div>

        <nav className="sidebar-nav">
          <NavLink to="/" end className={getNavClass}>
            Tổng quan
          </NavLink>

          <NavLink to="/patients" className={getNavClass}>
            Bệnh nhân
          </NavLink>

          <NavLink to="/visits" className={getNavClass}>
            Buổi khám
          </NavLink>

          <NavLink to="/service-catalogs" className={getNavClass}>
            Danh mục dịch vụ
          </NavLink>
        </nav>
      </aside>

      <main className="main-content">
        <Outlet />
      </main>
    </div>
  );
}
