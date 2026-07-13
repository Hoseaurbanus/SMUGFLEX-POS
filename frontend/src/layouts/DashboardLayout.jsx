import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from '../components/layout/Sidebar';
import TopBar from '../components/layout/TopBar';

export default function DashboardLayout() {
  const [collapsed, setCollapsed] = useState(false);

  return (
    <div className="app-container">
      <Sidebar collapsed={collapsed} onToggle={() => setCollapsed(!collapsed)} />
      <div className="main-content">
        <TopBar onMenuToggle={() => setCollapsed(!collapsed)} />
        <div className="page-content">
          <Outlet />
        </div>
      </div>
    </div>
  );
}
