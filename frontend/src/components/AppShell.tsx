"use client";

import { clearSession, getUser } from "@/lib/session";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useMemo } from "react";

type AppShellProps = {
  title: string;
  subtitle: string;
  children: React.ReactNode;
};

const navItems = [
  { href: "/dashboard", label: "Dashboard" },
  { href: "/users", label: "User Management" },
  { href: "/storage", label: "Cupboard and Place" },
  { href: "/items", label: "Item Management" },
  { href: "/borrows", label: "Borrow and Return" },
  { href: "/activity-log", label: "Activity Log" },
];

export function AppShell({
  title,
  subtitle,
  children,
}: AppShellProps): React.ReactElement {
  const router = useRouter();
  const pathname = usePathname();
  const user = useMemo(() => getUser(), []);

  const handleLogout = (): void => {
    clearSession();
    router.replace("/login");
  };

  return (
    <div className="app-shell">
      <aside className="side-panel">
        <div>
          <h1>IMS Console</h1>
          <p>Inventory Operations</p>
        </div>

        <nav>
          {navItems.map((item, index) => (
            <Link
              key={item.href}
              href={item.href}
              className={`nav-link ${pathname === item.href ? "active" : ""}`}
              style={{ animationDelay: `${index * 0.06}s` }}
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="session-card">
          <p>{user?.name ?? "Unknown user"}</p>
          <small>{user?.email ?? "No session"}</small>
          <button type="button" className="danger" onClick={handleLogout}>
            Logout
          </button>
        </div>
      </aside>

      <main className="main-panel">
        <header className="panel-header">
          <h2>{title}</h2>
          <p>{subtitle}</p>
        </header>

        <section className="panel-content">{children}</section>
      </main>
    </div>
  );
}
